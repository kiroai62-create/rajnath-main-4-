<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Astro\Tools;
use App\Domain\Payments\Checkout;
use App\Domain\Reviews\Reviews;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Reviews $reviews): View
    {
        $u = auth()->user();
        $paid = ['paid', 'partially_refunded'];
        $since = now()->subDays(30);
        $seeMoney = $u->can('finance.view');
        $series = [];
        if ($seeMoney) {
            $rows = DB::table('orders')->whereIn('status', $paid)->where('paid_at', '>=', now()->subDays(13)->startOfDay())
                ->selectRaw('DATE(paid_at) d, SUM(subtotal_minor - refunded_minor) v, COUNT(*) n')->groupBy('d')->pluck('v', 'd');
            for ($i = 13; $i >= 0; $i--) {
                $d = now()->subDays($i)->toDateString();
                $series[] = [$d, (int) ($rows[$d] ?? 0)];
            }
        }

        return view('admin.dashboard', [
            'kpis' => array_values(array_filter([
                $seeMoney ? ['Revenue, last 30 days', (int) DB::table('orders')->whereIn('status', $paid)->where('paid_at', '>=', $since)->sum(DB::raw('subtotal_minor - refunded_minor')), 'money', 'admin.finance.index'] : null,
                $seeMoney ? ['Paid orders, last 30 days', DB::table('orders')->whereIn('status', $paid)->where('paid_at', '>=', $since)->count(), 'count', 'admin.finance.index'] : null,
                $u->can('bookings.view') ? ['Consultations, next 7 days', DB::table('bookings')->where('status', 'confirmed')->whereBetween('start_utc', [now(), now()->addDays(7)])->count(), 'count', 'admin.ops.bookings'] : null,
                $u->can('customers.view') ? ['New customers, last 30 days', User::where('kind', User::KIND_CUSTOMER)->where('created_at', '>=', $since)->count(), 'count', 'admin.customers.index'] : null,
                $u->can('analytics.view') ? ['Website visitors, last 30 days', Cache::remember('dash:visitors30', 600, fn () => DB::table('analytics_events')->where('type', 'pageview')->where('occurred_at', '>=', $since)->distinct()->count('visitor')), 'count', 'admin.analytics.website'] : null,
                $u->can('analytics.view') ? ['Kundlis & calculators, last 30 days', DB::table('tool_runs')->whereIn('tool', array_keys(Tools::TOOLS))->where('created_at', '>=', $since)->count(), 'count', 'admin.analytics.tools'] : null,
                ['Average rating', $reviews->summary()['average'], 'rating', $u->can('content.review') ? 'admin.reviews.index' : null],
            ])),
            'series' => $series,
            'attention' => array_values(array_filter([
                ['New enquiries', DB::table('enquiries')->where('status', 'new')->count(), 'admin.enquiries.index', 'bookings.view'],
                ['New puja requests', DB::table('puja_requests')->where('status', 'new')->count(), 'admin.puja-requests.index', 'bookings.view'],
                ['Support requests open', DB::table('tickets')->whereIn('status', ['open', 'waiting_staff'])->count(), 'admin.ops.tickets', 'support.view'],
                ['Pujas to schedule', DB::table('puja_bookings')->whereIn('status', ['requested', 'awaiting_details'])->count(), 'admin.ops.pujas', 'puja.fulfil'],
                ['Reports to write', DB::table('report_jobs')->whereNotIn('status', ['completed', 'cancelled'])->count(), 'admin.ops.reports', 'reports.fulfil'],
                ['Reviews to check', DB::table('reviews')->where('status', 'pending')->count(), 'admin.reviews.index', 'content.review'],
                ['Privacy requests', DB::table('privacy_requests')->whereIn('case_status', ['open', 'in_progress'])->count(), 'admin.privacy.index', 'privacy.handle'],
                ['Astrologer applications', DB::table('practitioner_applications')->where('status', 'submitted')->count(), 'admin.applications.index', 'practitioners.view'],
                ['Legal page drafts', DB::table('legal_documents')->where('status', 'draft')->count(), 'admin.legal.index', 'legal.manage'],
            ], fn ($a) => $u->can($a[3]))),
            'orders' => $seeMoney ? DB::table('orders as o')->join('users as c', 'c.id', '=', 'o.user_id')->orderByDesc('o.id')->limit(6)
                ->get(['o.ref', 'o.kind', 'o.status', 'o.subtotal_minor', 'o.snapshot', 'o.created_at', 'c.name'])->map(function ($o) {
                    $o->label = Checkout::label($o->kind, json_decode($o->snapshot, true) ?: []);

                    return $o;
                }) : collect(),
            'upcoming' => $u->can('bookings.view') ? DB::table('bookings as b')->join('users as c', 'c.id', '=', 'b.user_id')->where('b.status', 'confirmed')
                ->where('b.start_utc', '>=', now())->orderBy('b.start_utc')->limit(6)->get(['b.ref', 'b.start_utc', 'b.mode', 'c.name']) : collect(),
            'stats' => [
                'Staff accounts' => User::whereHas('roles')->count(),
                'Customers' => User::where('kind', User::KIND_CUSTOMER)->count(),
                'Services on sale' => DB::table('offerings')->where('status', 'active')->count(),
                'Published articles' => DB::table('posts')->where('status', 'published')->count(),
            ],
            'recent' => $u->can('audit.view') ? DB::table('audit_logs')->orderByDesc('id')->limit(8)->get() : collect(),
        ]);
    }
}
