<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CommissionEntryStatus;
use App\Enums\PaymentMethod;
use App\Enums\PermissionName;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RecordCommissionPayoutRequest;
use App\Models\ChannelPartner;
use App\Models\CommissionEntry;
use App\Models\CommissionPayment;
use App\Services\CommissionService;
use App\Support\CommissionQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CommissionController extends Controller
{
    public function index(Request $request, CommissionQuery $commission): Response
    {
        $this->authorize('viewAny', CommissionEntry::class);

        $selected = $commission->selectedEntry($request);
        if ($selected && $request->user()?->cannot('view', $selected)) {
            $selected = null;
        }

        $selectedPayout = $commission->selectedPayout($request);
        if ($selectedPayout && $request->user()?->cannot('view', $selectedPayout)) {
            $selectedPayout = null;
        }

        return Inertia::render('Admin/Commission/Index', [
            'entries' => $commission->entries($request)->through(fn (CommissionEntry $entry) => $entry->toAdminArray()),
            'payouts' => $commission->payouts($request)->through(fn (CommissionPayment $payout) => $payout->toAdminArray()),
            'selected' => $selected?->toAdminArray(),
            'selectedPayout' => $selectedPayout?->toAdminArray(),
            'filters' => $commission->filters($request),
            'statuses' => CommissionEntryStatus::options(),
            'methods' => array_map(fn (PaymentMethod $method) => $method->value, PaymentMethod::cases()),
            'partners' => ChannelPartner::query()
                ->orderBy('display_name')
                ->get()
                ->map(fn (ChannelPartner $partner) => $partner->toSelectArray())
                ->values()
                ->all(),
            'can_manage' => $request->user()?->hasPermission(PermissionName::FinanceManage->value) === true,
        ]);
    }

    public function approve(Request $request, CommissionEntry $entry, CommissionService $commissions): RedirectResponse
    {
        $this->authorize('update', $entry);
        $commissions->approve($entry, $request->user());

        return back()->with('success', 'Commission entry approved.');
    }

    public function void(Request $request, CommissionEntry $entry, CommissionService $commissions): RedirectResponse
    {
        $this->authorize('update', $entry);
        $commissions->void($entry, $request->user(), $request->input('notes'));

        return back()->with('success', 'Commission entry voided.');
    }

    public function retry(Request $request, CommissionEntry $entry, CommissionService $commissions): RedirectResponse
    {
        $this->authorize('update', $entry);
        $commissions->retry($entry, $request->user());

        return back()->with('success', 'Commission entry retried.');
    }

    public function storePayout(
        RecordCommissionPayoutRequest $request,
        CommissionService $commissions,
    ): RedirectResponse {
        $payout = $commissions->recordPayout($request->validated(), $request->user());

        return redirect()
            ->route('admin.commission.index', ['view' => 'payouts', 'payout' => $payout->id])
            ->with('success', 'Commission payout recorded.');
    }
}
