<?php

namespace App\Services;

use App\Models\Application;

class RecruitmentAutomationScreeningAdvisor
{
    public function __construct(
        private ScreeningMatchService $match,
        private ScreeningWorkspaceService $workspace,
    ) {}

    /**
     * Reuses ScreeningMatchService scores and ScreeningWorkspaceService comparison.
     * Does not shortlist, reject, or change application status.
     *
     * @return array{
     *     match: array<string, mixed>,
     *     job_comparison: array<string, mixed>,
     *     evidence: array{matching_factors: list<string>, potential_gaps: list<string>, unavailable_factors: list<string>},
     *     application_origin: string,
     *     recommendation: string,
     *     auto_shortlist: false
     * }
     */
    public function recommend(Application $application): array
    {
        $match = $this->match->evaluate($application);
        $comparison = $this->workspace->jobComparison($application, $match);

        return [
            'match' => $match,
            'job_comparison' => $comparison,
            'evidence' => $this->evidenceFromMatch($match),
            'application_origin' => $application->user_id === null ? 'guest' : 'account',
            'recommendation' => $match['state'] === 'scored'
                ? 'Recruiter review recommended. Human decision remains final.'
                : 'Not enough data for a match score. Recruiter review still required.',
            'auto_shortlist' => false,
        ];
    }

    /**
     * @param  array<string, mixed>|null  $match
     * @return array{matching_factors: list<string>, potential_gaps: list<string>, unavailable_factors: list<string>}
     */
    public function evidenceFromMatch(?array $match): array
    {
        $matching = [];
        $gaps = [];
        $unavailable = [];

        foreach (is_array($match['factors'] ?? null) ? $match['factors'] : [] as $factor) {
            if (! is_array($factor)) {
                continue;
            }

            $label = (string) ($factor['label'] ?? $factor['key'] ?? 'Factor');
            $state = (string) ($factor['state'] ?? 'missing');

            if ($state === 'compared' && ($factor['matched'] ?? false) === true) {
                $matching[] = $label;
            } elseif ($state === 'compared') {
                $gaps[] = $label;
            } else {
                $unavailable[] = $label;
            }
        }

        return [
            'matching_factors' => $matching,
            'potential_gaps' => $gaps,
            'unavailable_factors' => $unavailable,
        ];
    }
}
