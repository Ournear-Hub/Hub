<?php

namespace App\Services;

use App\Models\Application;
use App\Models\Job;

class ScreeningMatchService
{
    public const DISCLAIMER = 'Match % compares recorded job and candidate fields only. It does not determine suitability. The human recruitment decision remains final.';

    /**
     * @return array{
     *     percent: int|null,
     *     state: string,
     *     label: string,
     *     disclaimer: string,
     *     comparable_count: int,
     *     matched_count: int,
     *     factors: list<array<string, mixed>>
     * }
     */
    public function evaluate(Application $application): array
    {
        $application->loadMissing([
            'job.classifiedLocation',
            'job.classifiedExperienceRange',
            'classifiedLocation',
            'classifiedExperienceRange',
            'classifiedQualification',
        ]);

        $job = $application->job;

        $factors = [
            $this->unavailableFactor(
                'skills',
                'Skills',
                'Job skills and candidate skills are not stored in V1.1.',
            ),
            $this->experienceFactor($application, $job),
            $this->unavailableFactor(
                'qualification',
                'Qualification',
                'Job qualification is not stored in V1.1, so this factor cannot be compared.',
            ),
            $this->locationFactor($application, $job),
            $this->unavailableFactor(
                'salary',
                'Salary',
                'Candidate current and expected salary are not stored in V1.1.',
            ),
            $this->unavailableFactor(
                'notice_period',
                'Notice period',
                'Notice period is not stored in V1.1.',
            ),
            $this->unavailableFactor(
                'industry_experience',
                'Industry experience',
                'Candidate industry experience is not stored in V1.1.',
            ),
            $this->unavailableFactor(
                'keywords',
                'Keywords',
                'Job keywords are not stored in V1.1.',
            ),
        ];

        $comparable = array_values(array_filter(
            $factors,
            fn (array $factor) => $factor['state'] === 'compared',
        ));
        $matched = array_values(array_filter(
            $comparable,
            fn (array $factor) => $factor['matched'] === true,
        ));

        if (count($comparable) < 2) {
            return [
                'percent' => null,
                'state' => 'not_enough_data',
                'label' => 'Not enough data',
                'disclaimer' => self::DISCLAIMER,
                'comparable_count' => count($comparable),
                'matched_count' => count($matched),
                'factors' => $factors,
            ];
        }

        $percent = (int) round((count($matched) / count($comparable)) * 100);

        return [
            'percent' => $percent,
            'state' => 'scored',
            'label' => $percent.'%',
            'disclaimer' => self::DISCLAIMER,
            'comparable_count' => count($comparable),
            'matched_count' => count($matched),
            'factors' => $factors,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function locationFactor(Application $application, ?Job $job): array
    {
        $candidate = $this->normalize($application->displayLocation());
        $jobValue = $this->normalize($job?->displayLocation());

        if ($candidate === null || $jobValue === null || $job === null) {
            return $this->missingFactor(
                'location',
                'Location',
                $candidate,
                $jobValue,
                'Location is missing on the application or the job.',
            );
        }

        $matched = $this->valuesMatch(
            $candidate,
            $jobValue,
            $application->location_id,
            $job->location_id,
        );

        return $this->comparedFactor('location', 'Location', $candidate, $jobValue, $matched);
    }

    /**
     * @return array<string, mixed>
     */
    private function experienceFactor(Application $application, ?Job $job): array
    {
        $candidate = $this->normalize($application->displayExperience());
        $jobValue = $this->normalize($job?->displayExperience());

        if ($candidate === null || $jobValue === null || $job === null) {
            return $this->missingFactor(
                'experience',
                'Experience',
                $candidate,
                $jobValue,
                'Experience is missing on the application or the job.',
            );
        }

        $matched = $this->valuesMatch(
            $candidate,
            $jobValue,
            $application->experience_range_id,
            $job->experience_range_id,
        );

        return $this->comparedFactor('experience', 'Experience', $candidate, $jobValue, $matched);
    }

    /**
     * @return array<string, mixed>
     */
    private function unavailableFactor(string $key, string $label, string $note): array
    {
        return [
            'key' => $key,
            'label' => $label,
            'state' => 'unavailable',
            'matched' => null,
            'contributed' => false,
            'candidate_value' => null,
            'job_value' => null,
            'note' => $note,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function missingFactor(
        string $key,
        string $label,
        ?string $candidateValue,
        ?string $jobValue,
        string $note,
    ): array {
        return [
            'key' => $key,
            'label' => $label,
            'state' => 'missing',
            'matched' => null,
            'contributed' => false,
            'candidate_value' => $candidateValue,
            'job_value' => $jobValue,
            'note' => $note,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function comparedFactor(
        string $key,
        string $label,
        string $candidateValue,
        string $jobValue,
        bool $matched,
    ): array {
        return [
            'key' => $key,
            'label' => $label,
            'state' => 'compared',
            'matched' => $matched,
            'contributed' => $matched,
            'candidate_value' => $candidateValue,
            'job_value' => $jobValue,
            'note' => $matched ? 'Recorded values match.' : 'Recorded values do not match.',
        ];
    }

    private function valuesMatch(?string $left, ?string $right, mixed $leftId, mixed $rightId): bool
    {
        if (filled($leftId) && filled($rightId) && (int) $leftId === (int) $rightId) {
            return true;
        }

        if ($left === null || $right === null) {
            return false;
        }

        if ($left === $right) {
            return true;
        }

        return str_contains($left, $right) || str_contains($right, $left);
    }

    private function normalize(?string $value): ?string
    {
        $normalized = strtolower(trim(preg_replace('/\s+/', ' ', (string) $value) ?? ''));

        return $normalized === '' ? null : $normalized;
    }
}
