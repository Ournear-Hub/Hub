<?php

namespace App\Services;

use App\Models\Job;
use App\Models\Qualification;
use App\Models\Skill;
use Illuminate\Support\Arr;

class JobListingRequirementService
{
    /**
     * @param  list<int|string>|null  $qualificationIds
     * @param  list<int|string>|null  $skillIds
     */
    public function sync(Job $job, ?array $qualificationIds, ?array $skillIds): void
    {
        if ($qualificationIds !== null) {
            $ids = Qualification::query()
                ->whereIn('id', Arr::wrap($qualificationIds))
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();
            $job->qualifications()->sync($ids);
        }

        if ($skillIds !== null) {
            $ids = Skill::query()
                ->whereIn('id', Arr::wrap($skillIds))
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();
            $job->skills()->sync($ids);
        }
    }
}
