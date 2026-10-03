<?php

namespace App\Support;

use App\Models\Campaign;
use App\Models\Job;
use App\Models\RecruitmentActivity;
use App\Models\Referral;
use App\Models\ReferralLink;
use App\Models\ShareEvent;
use App\Models\Task;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;

class CampaignQuery
{
    /**
     * @return LengthAwarePaginator<int, Campaign>
     */
    public function campaigns(Request $request): LengthAwarePaginator
    {
        $query = Campaign::query()->with('creator:id,name')->withCount(['links', 'shareEvents', 'referrals'])->latest('id');

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(function ($inner) use ($search) {
                $inner->where('name', 'like', '%'.$search.'%')
                    ->orWhere('business_id', 'like', '%'.$search.'%');
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        return $query->paginate(20)->withQueryString();
    }

    /**
     * @return LengthAwarePaginator<int, ReferralLink>
     */
    public function links(Request $request, ?int $campaignId): LengthAwarePaginator
    {
        $query = ReferralLink::query()
            ->with(['campaign:id,name', 'job:id,title', 'creator:id,name'])
            ->withCount(['shareEvents', 'referrals'])
            ->latest('id');

        if ($request->filled('link_status')) {
            $query->where('status', $request->string('link_status')->toString());
        }

        $this->scopeCampaign($query, $campaignId);

        return $query->paginate(20)->withQueryString();
    }

    /**
     * @return LengthAwarePaginator<int, ShareEvent>
     */
    public function shares(Request $request, ?int $campaignId): LengthAwarePaginator
    {
        $query = ShareEvent::query()
            ->with(['job:id,title', 'referralLink:id,code', 'campaign:id,name', 'user:id,name'])
            ->latest('id');

        if ($request->filled('channel')) {
            $query->where('channel', $request->string('channel')->toString());
        }

        $this->scopeCampaign($query, $campaignId);

        return $query->paginate(20)->withQueryString();
    }

    /**
     * @return LengthAwarePaginator<int, Referral>
     */
    public function referrals(Request $request, ?int $campaignId): LengthAwarePaginator
    {
        $query = Referral::query()
            ->with([
                'referralLink:id,code',
                'campaign:id,name',
                'referredUser:id,name,email,registration_source',
                'application:id,full_name',
                'job:id,title',
            ])
            ->latest('id');

        if ($request->filled('conversion_type')) {
            $query->where('conversion_type', $request->string('conversion_type')->toString());
        }

        $this->scopeCampaign($query, $campaignId);

        return $query->paginate(20)->withQueryString();
    }

    /**
     * @return LengthAwarePaginator<int, mixed>
     */
    public function emptyPage(Request $request): LengthAwarePaginator
    {
        return (new Paginator([], 0, 20, 1, [
            'path' => $request->url(),
            'pageName' => 'page',
        ]))->withQueryString();
    }

    public function tab(Request $request): string
    {
        $tab = $request->string('tab', 'campaigns')->toString();

        return in_array($tab, ['campaigns', 'links', 'shares', 'conversions'], true)
            ? $tab
            : 'campaigns';
    }

    public function selectedCampaign(Request $request): ?Campaign
    {
        $id = $request->integer('campaign');
        if ($id <= 0) {
            return null;
        }

        return Campaign::query()->with('creator:id,name')->find($id);
    }

    public function scopedCampaignId(Request $request, ?Campaign $authorized): ?int
    {
        $id = $request->integer('campaign');
        if ($id <= 0) {
            return null;
        }

        if ($authorized instanceof Campaign) {
            return (int) $authorized->id;
        }

        return $id;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function campaignOptions(?Campaign $selected): array
    {
        $options = Campaign::query()
            ->orderBy('name')
            ->limit(200)
            ->get();

        if ($selected && ! $options->contains(fn (Campaign $campaign) => $campaign->id === $selected->id)) {
            $options->prepend($selected);
        }

        return $options
            ->unique('id')
            ->values()
            ->map(fn (Campaign $campaign) => $campaign->toSelectArray())
            ->all();
    }

    /**
     * @return list<array{id: int, label: string}>
     */
    public function jobOptions(): array
    {
        return Job::query()
            ->latest('id')
            ->limit(200)
            ->get(['id', 'business_id', 'title'])
            ->map(fn (Job $job) => [
                'id' => $job->id,
                'label' => ($job->business_id ? $job->business_id.' · ' : '').$job->title,
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function activityFor(?Campaign $campaign): array
    {
        if (! $campaign) {
            return [];
        }

        return RecruitmentActivity::query()
            ->with('actor:id,name')
            ->where('campaign_id', $campaign->id)
            ->latest('id')
            ->limit(25)
            ->get()
            ->map(fn (RecruitmentActivity $item) => $item->toAdminArray())
            ->values()
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function tasksFor(?Campaign $campaign): array
    {
        if (! $campaign) {
            return [];
        }

        return Task::query()
            ->where('campaign_id', $campaign->id)
            ->latest('id')
            ->limit(25)
            ->get()
            ->map(fn (Task $task) => $task->toAdminArray())
            ->values()
            ->all();
    }

    /**
     * @return array<string, string>
     */
    public function filters(Request $request): array
    {
        return [
            'search' => $request->string('search')->toString(),
            'status' => $request->string('status')->toString(),
            'tab' => $this->tab($request),
            'campaign' => $request->string('campaign')->toString(),
            'link_status' => $request->string('link_status')->toString(),
            'channel' => $request->string('channel')->toString(),
            'conversion_type' => $request->string('conversion_type')->toString(),
        ];
    }

    private function scopeCampaign(mixed $query, ?int $campaignId): void
    {
        if ($campaignId !== null && $campaignId > 0) {
            $query->where('campaign_id', $campaignId);
        }
    }
}
