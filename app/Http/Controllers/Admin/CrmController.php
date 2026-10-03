<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CrmLeadSource;
use App\Enums\CrmOpportunityStatus;
use App\Enums\PermissionName;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ConvertCrmOpportunityRequest;
use App\Http\Requests\Admin\StoreCrmLeadRequest;
use App\Http\Requests\Admin\StoreCrmOpportunityRequest;
use App\Http\Requests\Admin\UpdateCrmLeadRequest;
use App\Http\Requests\Admin\UpdateCrmOpportunityRequest;
use App\Models\ChannelPartner;
use App\Models\CrmLead;
use App\Models\CrmOpportunity;
use App\Models\EmployerProfile;
use App\Models\Tag;
use App\Services\CrmLeadService;
use App\Services\CrmOpportunityService;
use App\Support\CrmQuery;
use App\Support\StaffOptions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CrmController extends Controller
{
    public function index(Request $request, CrmQuery $crm): Response
    {
        $this->authorize('viewAny', CrmLead::class);

        $selectedLead = $crm->selectedLead($request);
        if ($selectedLead && $request->user()?->cannot('view', $selectedLead)) {
            $selectedLead = null;
        }

        $selectedOpportunity = $crm->selectedOpportunity($request);
        if ($selectedOpportunity && $request->user()?->cannot('view', $selectedOpportunity)) {
            $selectedOpportunity = null;
        }

        if ($selectedOpportunity && ! $selectedLead) {
            $selectedLead = $selectedOpportunity->lead;
        }

        return Inertia::render('Admin/Crm/Index', [
            'leads' => $crm->adminIndex($request)->through(fn (CrmLead $lead) => $lead->toAdminArray()),
            'selected' => $selectedLead?->toAdminArray(),
            'selectedOpportunity' => $selectedOpportunity?->toAdminArray(),
            'filters' => $crm->filters($request),
            'sources' => array_map(fn (CrmLeadSource $source) => [
                'value' => $source->value,
                'label' => $source->label(),
            ], CrmLeadSource::cases()),
            'statuses' => CrmOpportunityStatus::options(),
            'staffUsers' => StaffOptions::users(),
            'partners' => ChannelPartner::query()
                ->where('status', '!=', \App\Enums\ChannelPartnerStatus::Suspended->value)
                ->orderBy('display_name')
                ->get()
                ->map(fn (ChannelPartner $partner) => $partner->toSelectArray())
                ->values()
                ->all(),
            'employers' => EmployerProfile::query()
                ->with('user:id,email,name')
                ->latest('id')
                ->limit(200)
                ->get()
                ->map(fn (EmployerProfile $employer) => $employer->toSelectArray())
                ->values()
                ->all(),
            'tags' => Tag::query()->orderBy('name')->get()->map(fn (Tag $tag) => $tag->toAdminArray())->all(),
            'can_manage' => $request->user()?->hasPermission(PermissionName::EmployersManage->value) === true,
            'duplicate_matches' => $request->session()->get('duplicate_matches', []),
        ]);
    }

    public function store(StoreCrmLeadRequest $request, CrmLeadService $leads): RedirectResponse
    {
        $lead = $leads->create($request->validated(), $request->user());

        return redirect()
            ->route('admin.crm.index', ['lead' => $lead->id])
            ->with('success', 'CRM lead created.');
    }

    public function update(UpdateCrmLeadRequest $request, CrmLead $lead, CrmLeadService $leads): RedirectResponse
    {
        $leads->update($lead, $request->validated(), $request->user());

        return back()->with('success', 'CRM lead updated.');
    }

    public function storeOpportunity(
        StoreCrmOpportunityRequest $request,
        CrmLead $lead,
        CrmOpportunityService $opportunities,
    ): RedirectResponse {
        $opportunity = $opportunities->create($lead, $request->validated(), $request->user());

        return redirect()
            ->route('admin.crm.index', ['lead' => $lead->id, 'opportunity' => $opportunity->id])
            ->with('success', 'CRM opportunity created.');
    }

    public function updateOpportunity(
        UpdateCrmOpportunityRequest $request,
        CrmOpportunity $opportunity,
        CrmOpportunityService $opportunities,
    ): RedirectResponse {
        $opportunities->update($opportunity, $request->validated(), $request->user());

        return back()->with('success', 'CRM opportunity updated.');
    }

    public function convert(
        ConvertCrmOpportunityRequest $request,
        CrmOpportunity $opportunity,
        CrmOpportunityService $opportunities,
    ): RedirectResponse {
        $converted = $opportunities->convert($opportunity, $request->validated(), $request->user());

        return redirect()
            ->route('admin.crm.index', ['lead' => $converted->crm_lead_id, 'opportunity' => $converted->id])
            ->with('success', 'CRM opportunity converted to an employer.');
    }
}
