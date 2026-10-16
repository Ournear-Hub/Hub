<?php

namespace App\Services;

use App\Models\Application;
use App\Models\Job;
use App\Support\EmployerVisibleCandidate;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class EmployerApplicantCompareService
{
    public const MAX_SELECTION = 5;

    public function __construct(
        private ScreeningMatchService $match,
    ) {}

    /**
     * @return list<int>
     */
    public function parseCompareIds(?string $raw): array
    {
        if ($raw === null || trim($raw) === '') {
            return [];
        }

        return collect(explode(',', $raw))
            ->map(fn (string $part) => (int) trim($part))
            ->filter(fn (int $id) => $id > 0)
            ->unique()
            ->values()
            ->take(self::MAX_SELECTION)
            ->all();
    }

    /**
     * @param  list<int>  $applicationIds
     * @return list<array<string, mixed>>
     */
    public function rowsForJob(Job $job, array $applicationIds): array
    {
        if ($applicationIds === []) {
            return [];
        }

        if (count($applicationIds) > self::MAX_SELECTION) {
            throw ValidationException::withMessages([
                'compare' => 'Select at most '.self::MAX_SELECTION.' applicants to compare.',
            ]);
        }

        /** @var Collection<int, Application> $applications */
        $applications = Application::query()
            ->where('job_id', $job->id)
            ->whereIn('id', $applicationIds)
            ->with(['user.candidateProfile.skills', 'user.candidateProfile.industry', 'job'])
            ->get()
            ->keyBy('id');

        if ($applications->count() !== count($applicationIds)) {
            throw ValidationException::withMessages([
                'compare' => 'One or more selected applicants are invalid for this job.',
            ]);
        }

        return collect($applicationIds)
            ->map(function (int $id) use ($applications) {
                $application = $applications->get($id);
                if (! $application instanceof Application) {
                    return null;
                }

                return $this->toCompareRow($application);
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function toCompareRow(Application $application): array
    {
        $base = EmployerVisibleCandidate::fromApplication($application);
        $match = $this->match->evaluate($application);
        $profile = $application->user?->candidateProfile;
        $relocationFactor = collect($match['factors'] ?? [])->firstWhere('key', 'relocation');

        return array_merge($base, [
            'match_percent' => $match['percent'],
            'match_label' => $match['label'],
            'match_disclaimer' => $match['disclaimer'],
            'skills' => $profile?->skillsDisplay(),
            'current_company' => $profile?->current_company,
            'relocation' => is_array($relocationFactor) ? ($relocationFactor['candidate_value'] ?? null) : null,
            'notice_period' => $profile?->notice_period?->label(),
            'current_salary' => $profile?->formatSalary($profile->current_salary),
            'expected_salary' => $profile?->formatSalary($profile->expected_salary),
        ]);
    }
}
