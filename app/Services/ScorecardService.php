<?php

namespace App\Services;

use App\Enums\RecruitmentActivityType;
use App\Models\ApplicationInterview;
use App\Models\InterviewScorecard;
use App\Models\ScorecardTemplate;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ScorecardService
{
    public function __construct(private RecruitmentActivityService $activities) {}

    /**
     * @param  array{
     *     name: string,
     *     description?: string|null,
     *     criteria: list<array{key?: string, label: string}>,
     *     is_active?: bool
     * }  $data
     */
    public function saveTemplate(?ScorecardTemplate $template, array $data, User $actor): ScorecardTemplate
    {
        $attributes = [
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'criteria' => $data['criteria'],
            'is_active' => array_key_exists('is_active', $data) ? (bool) $data['is_active'] : ($template?->is_active ?? true),
        ];

        if ($template === null) {
            $attributes['created_by'] = $actor->id;

            return ScorecardTemplate::query()->create($attributes);
        }

        $template->update($attributes);

        return $template->fresh() ?? $template;
    }

    /**
     * Scores are recorded only. They never change application, screening, submission, or placement status.
     *
     * @param  array{
     *     scorecard_template_id?: int|null,
     *     qualitative?: array<string, mixed>|null,
     *     numeric_score?: float|int|string|null,
     *     notes?: string|null
     * }  $data
     */
    public function submit(ApplicationInterview $interview, array $data, User $actor): InterviewScorecard
    {
        return DB::transaction(function () use ($interview, $data, $actor) {
            $scorecard = InterviewScorecard::query()->updateOrCreate(
                ['application_interview_id' => $interview->id],
                [
                    'scorecard_template_id' => $data['scorecard_template_id'] ?? null,
                    'qualitative' => $data['qualitative'] ?? [],
                    'numeric_score' => $data['numeric_score'] ?? null,
                    'notes' => $data['notes'] ?? null,
                    'submitted_by' => $actor->id,
                    'submitted_at' => now(),
                ]
            );

            $this->activities->record(
                actor: $actor,
                type: RecruitmentActivityType::ScorecardSubmitted,
                application: $interview->application,
                interview: $interview,
                body: 'Interview scorecard submitted. Score does not change hiring status.',
            );

            return $scorecard->fresh() ?? $scorecard;
        });
    }
}
