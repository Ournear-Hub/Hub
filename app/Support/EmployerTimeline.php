<?php

namespace App\Support;

use App\Enums\RecruitmentActivityType;
use App\Enums\UserActivityType;
use App\Models\EmployerProfile;
use App\Models\RecruitmentActivity;
use App\Models\UserActivity;

class EmployerTimeline
{
    /**
     * Combined user + recruitment activity for the employer drawer.
     *
     * @return list<array{id: string, title: string, meta: string}>
     */
    public function compose(EmployerProfile $profile, int $limit = 40): array
    {
        $items = [];

        if ($profile->user_id) {
            $items = array_merge($items, UserActivity::query()
                ->where('user_id', $profile->user_id)
                ->with('actor:id,name')
                ->latest('id')
                ->limit($limit)
                ->get()
                ->map(function (UserActivity $activity) {
                    $type = $activity->type instanceof UserActivityType
                        ? $activity->type
                        : UserActivityType::tryFrom((string) $activity->type);

                    return [
                        'id' => 'user-'.$activity->id,
                        'title' => $activity->description ?: ($type?->label() ?? 'Account activity'),
                        'meta' => trim(implode(' · ', array_filter([
                            $type?->label(),
                            $activity->actor?->name,
                            $activity->created_at?->toDateTimeString(),
                        ]))),
                        'sort_at' => $activity->created_at?->timestamp ?? 0,
                    ];
                })
                ->all());
        }

        $jobIds = $profile->jobs()->pluck('id');

        $items = array_merge($items, RecruitmentActivity::query()
            ->with('actor:id,name')
            ->where(function ($query) use ($profile, $jobIds) {
                $query->where('employer_profile_id', $profile->id)
                    ->orWhereHas('submission', fn ($submission) => $submission->where('employer_profile_id', $profile->id));

                if ($jobIds->isNotEmpty()) {
                    $query->orWhereHas('application', fn ($application) => $application->whereIn('job_id', $jobIds));
                }
            })
            ->latest('id')
            ->limit($limit)
            ->get()
            ->unique('id')
            ->values()
            ->map(function (RecruitmentActivity $activity) {
                $type = $activity->type instanceof RecruitmentActivityType
                    ? $activity->type
                    : RecruitmentActivityType::tryFrom((string) $activity->type);

                $typeLabel = $type?->value;

                $title = $activity->body ?: ($activity->from_status && $activity->to_status
                    ? $activity->from_status.' → '.$activity->to_status
                    : ($typeLabel ?? 'Recruitment activity'));

                return [
                    'id' => 'recruitment-'.$activity->id,
                    'title' => $title,
                    'meta' => trim(implode(' · ', array_filter([
                        $typeLabel,
                        $activity->actor?->name,
                        $activity->created_at?->toDateTimeString(),
                    ]))),
                    'sort_at' => $activity->created_at?->timestamp ?? 0,
                ];
            })
            ->all());

        usort($items, fn (array $left, array $right) => ($right['sort_at'] ?? 0) <=> ($left['sort_at'] ?? 0));

        return array_map(function (array $item) {
            unset($item['sort_at']);

            return $item;
        }, array_slice($items, 0, $limit));
    }
}
