<?php

namespace App\Support;

use App\Enums\RecruitmentActivityType;
use App\Enums\UserActivityType;
use App\Models\CandidateProfile;
use App\Models\RecruitmentActivity;
use App\Models\UserActivity;

class CandidateTimeline
{
    /**
     * Combined user + recruitment activity for the talent drawer.
     *
     * @return list<array{id: string, title: string, meta: string}>
     */
    public function compose(CandidateProfile $profile, int $limit = 40): array
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

        $applicationIds = $profile->applications()->pluck('id');
        if ($applicationIds->isNotEmpty()) {
            $items = array_merge($items, RecruitmentActivity::query()
                ->whereIn('application_id', $applicationIds)
                ->with('actor:id,name')
                ->latest('id')
                ->limit($limit)
                ->get()
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
        }

        usort($items, fn (array $left, array $right) => ($right['sort_at'] ?? 0) <=> ($left['sort_at'] ?? 0));

        return array_map(function (array $item) {
            unset($item['sort_at']);

            return $item;
        }, array_slice($items, 0, $limit));
    }
}
