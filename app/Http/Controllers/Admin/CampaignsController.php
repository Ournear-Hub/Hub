<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CampaignStatus;
use App\Enums\PermissionName;
use App\Enums\ReferralConversionType;
use App\Enums\ReferralLinkStatus;
use App\Enums\ShareChannel;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCampaignRequest;
use App\Http\Requests\Admin\StoreReferralLinkRequest;
use App\Http\Requests\Admin\TransitionCampaignRequest;
use App\Http\Requests\Admin\UpdateCampaignRequest;
use App\Models\Campaign;
use App\Models\Referral;
use App\Models\ReferralLink;
use App\Models\ShareEvent;
use App\Services\CampaignService;
use App\Services\ReferralLinkService;
use App\Support\CampaignQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CampaignsController extends Controller
{
    public function index(Request $request, CampaignQuery $campaigns): Response
    {
        $this->authorize('viewAny', Campaign::class);

        $selected = $campaigns->selectedCampaign($request);
        if ($selected && $request->user()?->cannot('view', $selected)) {
            $selected = null;
        }

        $tab = $campaigns->tab($request);
        $scopedId = $campaigns->scopedCampaignId($request, $selected);
        $empty = $campaigns->emptyPage($request);

        return Inertia::render('Admin/Campaigns/Index', [
            'campaigns' => $tab === 'campaigns'
                ? $campaigns->campaigns($request)->through(fn (Campaign $campaign) => $campaign->toAdminArray())
                : $empty,
            'links' => $tab === 'links'
                ? $campaigns->links($request, $scopedId)->through(fn (ReferralLink $link) => $link->toAdminArray())
                : $empty,
            'shares' => $tab === 'shares'
                ? $campaigns->shares($request, $scopedId)->through(fn (ShareEvent $event) => $event->toAdminArray())
                : $empty,
            'referrals' => $tab === 'conversions'
                ? $campaigns->referrals($request, $scopedId)->through(fn (Referral $referral) => $referral->toAdminArray())
                : $empty,
            'selected' => $selected?->toAdminArray(),
            'activity' => $campaigns->activityFor($selected),
            'assigned_work' => $campaigns->tasksFor($selected),
            'filters' => $campaigns->filters($request),
            'statuses' => CampaignStatus::options(),
            'link_statuses' => ReferralLinkStatus::options(),
            'channels' => ShareChannel::options(),
            'conversion_types' => ReferralConversionType::options(),
            'campaign_options' => in_array($tab, ['links', 'shares', 'conversions'], true)
                ? $campaigns->campaignOptions($selected)
                : [],
            'job_options' => $tab === 'links' ? $campaigns->jobOptions() : [],
            'can_manage' => $request->user()?->hasPermission(PermissionName::CampaignsManage->value) === true,
        ]);
    }

    public function store(StoreCampaignRequest $request, CampaignService $campaigns): RedirectResponse
    {
        $campaign = $campaigns->create($request->validated(), $request->user());

        return redirect()
            ->route('admin.campaigns.index', ['campaign' => $campaign->id])
            ->with('success', 'Campaign created.');
    }

    public function update(UpdateCampaignRequest $request, Campaign $campaign, CampaignService $campaigns): RedirectResponse
    {
        $campaigns->update($campaign, $request->validated(), $request->user());

        return back()->with('success', 'Campaign updated.');
    }

    public function changeStatus(TransitionCampaignRequest $request, Campaign $campaign, CampaignService $campaigns): RedirectResponse
    {
        $campaigns->changeStatus($campaign, $request->validated('status'), $request->user());

        return back()->with('success', 'Campaign status updated.');
    }

    public function storeLink(StoreReferralLinkRequest $request, ReferralLinkService $links): RedirectResponse
    {
        $link = $links->create($request->validated(), $request->user());

        return redirect()
            ->route('admin.campaigns.index', ['tab' => 'links', 'campaign' => $link->campaign_id])
            ->with('success', 'Referral link created.');
    }

    public function disableLink(Request $request, ReferralLink $referralLink, ReferralLinkService $links): RedirectResponse
    {
        $this->authorize('update', $referralLink);
        $links->disable($referralLink, $request->user());

        return back()->with('success', 'Referral link disabled.');
    }
}
