<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ChannelPartnerStatus;
use App\Enums\EmployerAttributionStatus;
use App\Enums\PartnerAgreementStatus;
use App\Enums\PermissionName;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreChannelPartnerRequest;
use App\Http\Requests\Admin\StoreEmployerAttributionRequest;
use App\Http\Requests\Admin\StorePartnerAgreementRequest;
use App\Http\Requests\Admin\UpdateChannelPartnerRequest;
use App\Models\ChannelPartner;
use App\Models\ChannelPartnerAgreement;
use App\Models\EmployerAttribution;
use App\Models\EmployerProfile;
use App\Services\AttributionService;
use App\Services\ChannelPartnerService;
use App\Support\PartnerQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PartnersController extends Controller
{
    public function index(Request $request, PartnerQuery $partners): Response
    {
        $this->authorize('viewAny', ChannelPartner::class);

        $selected = $partners->selectedPartner($request);
        if ($selected && $request->user()?->cannot('view', $selected)) {
            $selected = null;
        }

        return Inertia::render('Admin/Partners/Index', [
            'partners' => $partners->partners($request)->through(fn (ChannelPartner $partner) => $partner->toAdminArray()),
            'agreements' => $partners->agreements($request)->through(fn (ChannelPartnerAgreement $agreement) => $agreement->toAdminArray()),
            'attributions' => $partners->attributions($request)->through(fn (EmployerAttribution $attribution) => $attribution->toAdminArray()),
            'selected' => $selected?->toAdminArray(),
            'filters' => $partners->filters($request),
            'statuses' => ChannelPartnerStatus::options(),
            'agreement_statuses' => PartnerAgreementStatus::options(),
            'attribution_statuses' => EmployerAttributionStatus::options(),
            'employers' => EmployerProfile::query()
                ->latest('id')
                ->limit(200)
                ->get()
                ->map(fn (EmployerProfile $employer) => $employer->toSelectArray())
                ->values()
                ->all(),
            'partner_options' => ChannelPartner::query()
                ->orderBy('display_name')
                ->get()
                ->map(fn (ChannelPartner $partner) => $partner->toSelectArray())
                ->values()
                ->all(),
            'can_manage' => $request->user()?->hasPermission(PermissionName::EmployersManage->value) === true,
        ]);
    }

    public function store(StoreChannelPartnerRequest $request, ChannelPartnerService $partners): RedirectResponse
    {
        $partner = $partners->create($request->validated(), $request->user());

        return redirect()
            ->route('admin.partners.index', ['partner' => $partner->id])
            ->with('success', 'Channel partner created as pending.');
    }

    public function update(
        UpdateChannelPartnerRequest $request,
        ChannelPartner $partner,
        ChannelPartnerService $partners,
    ): RedirectResponse {
        $partners->update($partner, $request->validated(), $request->user());

        return back()->with('success', 'Channel partner updated.');
    }

    public function approve(Request $request, ChannelPartner $partner, ChannelPartnerService $partners): RedirectResponse
    {
        $this->authorize('update', $partner);
        $partners->approve($partner, $request->user());

        return back()->with('success', 'Channel partner approved.');
    }

    public function suspend(Request $request, ChannelPartner $partner, ChannelPartnerService $partners): RedirectResponse
    {
        $this->authorize('update', $partner);
        $partners->suspend($partner, $request->user());

        return back()->with('success', 'Channel partner suspended.');
    }

    public function storeAgreement(
        StorePartnerAgreementRequest $request,
        ChannelPartner $partner,
        ChannelPartnerService $partners,
    ): RedirectResponse {
        $agreement = $partners->createAgreement($partner, $request->validated(), $request->user());

        return redirect()
            ->route('admin.partners.index', ['tab' => 'agreements', 'partner' => $partner->id, 'agreement' => $agreement->id])
            ->with('success', 'Partner agreement created.');
    }

    public function activateAgreement(
        Request $request,
        ChannelPartnerAgreement $agreement,
        ChannelPartnerService $partners,
    ): RedirectResponse {
        $this->authorize('update', $agreement);
        $partners->activateAgreement($agreement, $request->user());

        return back()->with('success', 'Partner agreement activated.');
    }

    public function storeAttribution(
        StoreEmployerAttributionRequest $request,
        AttributionService $attributions,
    ): RedirectResponse {
        $attribution = $attributions->create($request->validated(), $request->user());

        return redirect()
            ->route('admin.partners.index', [
                'tab' => 'attributions',
                'partner' => $attribution->channel_partner_id,
                'attribution' => $attribution->id,
            ])
            ->with('success', 'Employer attribution recorded.');
    }

    public function confirmAttribution(
        Request $request,
        EmployerAttribution $attribution,
        AttributionService $attributions,
    ): RedirectResponse {
        $this->authorize('update', $attribution);
        $attributions->confirm($attribution, $request->user());

        return back()->with('success', 'Employer attribution confirmed.');
    }

    public function disputeAttribution(
        Request $request,
        EmployerAttribution $attribution,
        AttributionService $attributions,
    ): RedirectResponse {
        $this->authorize('update', $attribution);
        $attributions->dispute($attribution, $request->user(), $request->input('notes'));

        return back()->with('success', 'Employer attribution disputed.');
    }

    public function voidAttribution(
        Request $request,
        EmployerAttribution $attribution,
        AttributionService $attributions,
    ): RedirectResponse {
        $this->authorize('update', $attribution);
        $attributions->void($attribution, $request->user());

        return back()->with('success', 'Employer attribution voided.');
    }
}
