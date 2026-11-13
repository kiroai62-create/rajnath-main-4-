<?php

namespace App\Http\Controllers\Web;

use App\Domain\Puja\PujaRequests;
use App\Http\Controllers\Controller;
use App\Models\PujaRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Public "Puja on request" form: collects the visitor's requirements; staff call back to fix the date. */
class PujaRequestController extends Controller
{
    public function __construct(private PujaRequests $requests) {}

    public function create(Request $request): View
    {
        $types = $this->requests->types();
        $wanted = trim((string) $request->query('puja', ''));

        return view('public.puja-request.create', [
            'types' => $types,
            'intro' => $this->requests->intro(),
            'open' => $this->requests->open(),
            'selected' => $wanted !== '' && in_array($wanted, $types, true) ? $wanted : ($wanted !== '' ? 'other' : null),
            'other' => $wanted !== '' && ! in_array($wanted, $types, true) ? mb_substr($wanted, 0, 150) : null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        // Honeypot: real people never fill the hidden "website" field.
        if (filled($request->input('website'))) {
            return back()->with('status', 'Thank you. We will contact you shortly.');
        }
        abort_unless($this->requests->open(), 404);
        $rules = PujaRequests::rules();
        $rules['puja_type'][] = Rule::in([...$this->requests->types(), 'other']);
        $data = $request->validate($rules, [
            'consent.accepted' => 'Please agree to be contacted about your request.',
            'puja_other.required_if' => 'Please write the name of the puja you need.',
            'address.required_if' => 'Please write the address or city for a puja at home.',
        ]);
        $phone = PujaRequests::phone($data['phone']);
        if (! $phone) {
            return back()->withInput()->withErrors(['phone' => 'Enter a 10-digit mobile number, or include the country code.']);
        }
        $r = $this->requests->create($data, $phone, $request->user(), $request->ip());

        return redirect()->route('puja-request.thanks', $r->ref);
    }

    public function thanks(string $ref): View
    {
        $r = PujaRequest::where('ref', $ref)->where('created_at', '>', now()->subHour())->firstOrFail();

        return view('public.puja-request.thanks', ['r' => $r]);
    }
}
