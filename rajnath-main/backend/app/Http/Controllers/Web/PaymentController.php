<?php

namespace App\Http\Controllers\Web;

use App\Domain\Booking\Slots;
use App\Domain\Ledger\Ledger;
use App\Domain\Payments\Checkout;
use App\Domain\Payments\Razorpay;
use App\Domain\Payments\ServiceDetails;
use App\Domain\Reports\ReportJobs;
use App\Http\Controllers\Controller;
use App\Models\BirthProfile;
use App\Models\OfferingVariant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;
use RuntimeException;

class PaymentController extends Controller
{
    public function __construct(private Checkout $checkout, private Razorpay $rzp, private Ledger $ledger) {}

    public function wallet(Request $request): View
    {
        $u = $request->user();
        $acct = DB::table('ledger_accounts')->where('code', "customer_wallet:{$u->id}")->first();
        $entries = $acct ? DB::table('ledger_entries as e')->join('ledger_transactions as t', 't.id', '=', 'e.transaction_id')
            ->where('e.account_id', $acct->id)->orderByDesc('e.id')->limit(50)->get(['e.direction', 'e.amount_minor', 't.kind', 't.memo', 't.occurred_at']) : collect();

        return view('account.wallet', [
            'balance' => $acct ? (int) $acct->balance_minor : 0, 'entries' => $entries,
            'packages' => DB::table('recharge_packages')->where('active', true)->orderBy('sort')->orderBy('amount_minor')->get(),
            'payable' => $this->rzp->configured(),
        ]);
    }

    public function orders(Request $request): View
    {
        return view('account.orders', ['orders' => DB::table('orders')->where('user_id', $request->user()->id)->orderByDesc('id')->paginate(20)]);
    }

    public function receipt(Request $request, string $ref): View
    {
        $o = DB::table('orders')->where('ref', $ref)->where('user_id', $request->user()->id)->first();
        abort_unless($o, 404);

        return view('account.receipt', ['o' => $o, 'snap' => json_decode($o->snapshot, true), 'payment' => DB::table('payments')->where('order_id', $o->id)->where('status', 'captured')->first(),
            'refunds' => DB::table('refunds')->where('order_id', $o->id)->where('status', 'processed')->get()]);
    }

    public function buy(Request $request): RedirectResponse
    {
        $d = $request->validate(['variant' => ['required', 'string', 'size:26'], 'use_wallet' => ['nullable', 'boolean'], 'key' => ['required', 'string', 'max:64'], 'coupon' => ['nullable', 'string', 'max:32']]);
        $v = OfferingVariant::where('ref', $d['variant'])->firstOrFail();
        if (Slots::bookable($v) && $v->active && $v->price_minor > 0 && $v->offering?->status === 'active') {
            return redirect()->route('account.book', $v->ref); // choose a time first
        }
        if (ServiceDetails::kind($v) && $v->active && $v->price_minor > 0 && $v->offering?->status === 'active') {
            // details first, payment last
            return redirect()->route('checkout.details', ['variant' => $v->ref, 'use_wallet' => (int) ($d['use_wallet'] ?? 0), 'coupon' => $d['coupon'] ?? null]);
        }
        try {
            $r = $this->checkout->startServiceOrder($request->user(), $v, (bool) ($d['use_wallet'] ?? false), $d['key'], [], $d['coupon'] ?? null);
        } catch (RuntimeException $e) {
            report($e);

            return back()->withErrors(['variant' => 'Online payment is not available right now. Please send a request instead.']);
        }

        return redirect()->route('checkout.show', $r['order']->ref);
    }

    public function recharge(Request $request): RedirectResponse
    {
        $d = $request->validate(['package' => ['required', 'integer'], 'key' => ['required', 'string', 'max:64']]);
        $pkg = DB::table('recharge_packages')->where('id', $d['package'])->where('active', true)->first();
        abort_unless($pkg, 404);
        try {
            $r = $this->checkout->startRecharge($request->user(), $pkg, $d['key']);
        } catch (RuntimeException $e) {
            report($e);

            return back()->withErrors(['package' => 'Online payment is not available right now.']);
        }

        return redirect()->route('checkout.show', $r['order']->ref);
    }

    public function show(Request $request, string $ref): View|RedirectResponse
    {
        $o = DB::table('orders')->where('ref', $ref)->where('user_id', $request->user()->id)->first();
        abort_unless($o, 404);
        if ($o->status !== 'pending_payment') {
            return $this->afterPayment($o->ref);
        }

        return view('account.checkout', ['o' => $o, 'snap' => json_decode($o->snapshot, true), 'rzp' => $this->checkout->checkoutPayload($o)['razorpay'], 'user' => $request->user()]);
    }

    /** Browser callback after Razorpay Checkout. Signature verified server-side; amount checked on capture. */
    public function verify(Request $request, string $ref): RedirectResponse
    {
        $o = DB::table('orders')->where('ref', $ref)->where('user_id', $request->user()->id)->first();
        abort_unless($o, 404);
        $d = $request->validate(['razorpay_payment_id' => ['required', 'string', 'max:60'], 'razorpay_order_id' => ['required', 'string', 'max:60'], 'razorpay_signature' => ['required', 'string', 'max:128']]);
        $p = DB::table('payments')->where('order_id', $o->id)->where('provider_order_id', $d['razorpay_order_id'])->first();
        if (! $p || ! $this->rzp->verifyCheckoutSignature($d['razorpay_order_id'], $d['razorpay_payment_id'], $d['razorpay_signature'])) {
            return redirect()->route('checkout.show', $o->ref)->withErrors(['payment' => 'We could not verify this payment. If money was deducted, it will be confirmed automatically within a few minutes or refunded.']);
        }
        // Confirm the captured amount with Razorpay rather than trusting the browser.
        try {
            $items = $this->rzp->orderPayments($d['razorpay_order_id']);
            $cap = collect($items)->firstWhere('id', $d['razorpay_payment_id']);
            if ($cap && $cap['status'] === 'captured') {
                $this->checkout->applyCapture($d['razorpay_order_id'], $cap['id'], (int) $cap['amount'], $cap['method'] ?? null);
            } else {
                DB::table('payments')->where('id', $p->id)->update(['status' => 'authorized', 'provider_payment_id' => $d['razorpay_payment_id'], 'updated_at' => now()]);
            }
        } catch (RuntimeException $e) {
            report($e);
            DB::table('payments')->where('id', $p->id)->update(['status' => 'ambiguous', 'provider_payment_id' => $d['razorpay_payment_id'], 'updated_at' => now()]);
        }

        return $this->afterPayment($o->ref);
    }

    /** Sends the customer straight to the next step of a paid order (upload, sankalp, details), else the receipt. */
    private function afterPayment(string $ref): RedirectResponse
    {
        $o = DB::table('orders')->where('ref', $ref)->first();
        $next = in_array($o->status, ['paid', 'partially_refunded'], true) ? Checkout::next($o) : null;
        $route = match ($next['type'] ?? null) {
            'palm_upload' => route('account.palm.show', $next['ref']),
            'puja_details' => route('account.puja.show', $next['ref']),
            'report_details' => route('account.reports.show', $next['ref']),
            default => route('account.receipt', $o->ref),
        };

        return redirect($route)->with('status', $next && $next['type'] !== 'booking' && $next['type'] !== 'shipment'
            ? 'Payment received. Please complete this step so we can begin.' : null);
    }

    public function cancel(Request $request, string $ref): RedirectResponse
    {
        $o = DB::table('orders')->where('ref', $ref)->where('user_id', $request->user()->id)->first();
        abort_unless($o, 404);
        $this->checkout->cancel($o);

        return redirect()->route('account.orders')->with('status', 'Order cancelled.');
    }

    /** Form for the details of a paid option (palm photos, sankalp, birth details, visit address). */
    public function details(Request $request, string $variant): View|RedirectResponse
    {
        $v = $this->payableVariant($variant);
        $kind = ServiceDetails::kind($v);
        if (! $kind) {
            return redirect()->route('services.show', $v->offering->slug);
        }

        return view('account.checkout-details', ['v' => $v, 'o' => $v->offering, 't' => $v->offering->t(), 'kind' => $kind, 'user' => $request->user(),
            'profiles' => BirthProfile::where('user_id', $request->user()->id)->orderByDesc('is_primary')->orderBy('id')->get(),
            'twoProfiles' => ReportJobs::needsTwo($v->offering->slug), 'useWallet' => $request->boolean('use_wallet'),
            'coupon' => mb_substr((string) $request->query('coupon', ''), 0, 32), 'key' => self::newKey()]);
    }

    public function submitDetails(Request $request, string $variant): RedirectResponse
    {
        $v = $this->payableVariant($variant);
        $kind = ServiceDetails::kind($v);
        abort_unless($kind, 404);
        if ($kind === 'puja') {
            // empty name rows of the form are ignored
            $request->merge(['devotees' => array_values(array_filter((array) $request->input('devotees', []), fn ($x) => is_array($x) && trim((string) ($x['name'] ?? '')) !== ''))]);
        }
        $d = $request->validate(ServiceDetails::rules($kind, $v) + ['key' => ['required', 'string', 'max:64'], 'use_wallet' => ['nullable', 'boolean'], 'coupon' => ['nullable', 'string', 'max:32']], ServiceDetails::messages());
        $extra = ServiceDetails::prepare($kind, $request->user(), $v, $d, ['left' => $request->file('left'), 'right' => $request->file('right')]);
        try {
            $r = $this->checkout->startServiceOrder($request->user(), $v, (bool) ($d['use_wallet'] ?? false), $d['key'], $extra, $d['coupon'] ?? null);
        } catch (RuntimeException $e) {
            report($e);

            return back()->withInput()->withErrors(['variant' => 'Online payment is not available right now. Please send a request instead.']);
        }

        return redirect()->route('checkout.show', $r['order']->ref);
    }

    private function payableVariant(string $ref): OfferingVariant
    {
        $v = OfferingVariant::with('offering.family', 'offering.translations')->where('ref', $ref)->firstOrFail();
        abort_unless($v->active && $v->price_minor > 0 && $v->offering?->status === 'active', 404);

        return $v;
    }

    public static function newKey(): string
    {
        return (string) Str::uuid();
    }
}
