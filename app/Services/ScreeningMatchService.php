<?php

namespace App\Services;

use App\Models\Application;
use App\Models\CandidateProfile;
use App\Models\Job;

class ScreeningMatchService
{
    public const DISCLAIMER = 'Match % compares recorded fields only; does not determine suitability; human decision remains final.';

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
            'job.classifiedIndustry',
            'classifiedLocation',
            'classifiedExperienceRange',
            'classifiedQualification',
            'user.candidateProfile.industry',
            'user.candidateProfile.preferredLocations',
            'user.candidateProfile.skills',
            'user.candidateProfile.employmentHistories.industry',
        ]);

        $job = $application->job;
        $profile = $application->user?->candidateProfile;

        $factors = [
            $this->skillsFactor($profile),
            $this->experienceFactor($application, $job),
            $this->qualificationFactor($application),
            $this->locationFactor($application, $job),
            $this->salaryFactor($job, $profile),
            $this->noticeFactor($job, $profile),
            $this->industryFactor($job, $profile, $application),
            $this->relocationFactor($application, $job, $profile),
            $this->designationFactor($job, $profile),
            $this->unavailableFactor(
                'keywords',
                'Keywords',
                'Job does not have structured keyword data.',
            ),
            $this->industryExperienceFactor($job, $profile, $application),
        ];

        $comparable = collect($factors)->where('state', 'compared');
        $matched = $comparable->where('matched', true)->count();
        $comparableCount = $comparable->count();
        $percent = $comparableCount >= 2
            ? (int) round(($matched / $comparableCount) * 100)
            : null;

        return [
            'percent' => $percent,
            'state' => $percent === null ? 'not_enough_data' : 'scored',
            'label' => $percent === null ? 'Not enough data' : $percent.'%',
            'disclaimer' => self::DISCLAIMER,
            'comparable_count' => $comparableCount,
            'matched_count' => $matched,
            'factors' => $factors,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function experienceFactor(Application $application, ?Job $job): array
    {
        $candidateDisplay = $this->display($application->displayExperience());
        $jobDisplay = $this->display($job?->displayExperience());
        $candidateId = $application->experience_range_id;
        $jobId = $job?->experience_range_id;

        if (! $this->hasRecordedValue($candidateDisplay, $candidateId) || ! $this->hasRecordedValue($jobDisplay, $jobId)) {
            return $this->missingFactor(
                'experience',
                'Experience',
                $candidateDisplay,
                $jobDisplay,
                'Experience is compared only when both the application snapshot and the job have recorded experience.',
                'snapshot',
            );
        }

        $matched = $this->recordsMatch(
            $candidateId,
            $candidateDisplay,
            $jobId,
            $jobDisplay,
        );

        return $this->comparedFactor(
            'experience',
            'Experience',
            $candidateDisplay,
            $jobDisplay,
            $matched,
            'Compared from the application snapshot and the job experience field.',
            'snapshot',
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function locationFactor(Application $application, ?Job $job): array
    {
        $candidateDisplay = $this->display($application->displayLocation());
        $jobDisplay = $this->display($job?->displayLocation());
        $candidateId = $application->location_id;
        $jobId = $job?->location_id;

        if (! $this->hasRecordedValue($candidateDisplay, $candidateId) || ! $this->hasRecordedValue($jobDisplay, $jobId)) {
            return $this->missingFactor(
                'location',
                'Location',
                $candidateDisplay,
                $jobDisplay,
                'Location is compared only when both the application snapshot and the job have recorded location.',
                'snapshot',
            );
        }

        $matched = $this->recordsMatch(
            $candidateId,
            $candidateDisplay,
            $jobId,
            $jobDisplay,
        );

        return $this->comparedFactor(
            'location',
            'Location',
            $candidateDisplay,
            $jobDisplay,
            $matched,
            'Compared from the application snapshot and the job location field.',
            'snapshot',
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function industryFactor(?Job $job, ?CandidateProfile $profile, Application $application): array
    {
        $jobDisplay = $this->display($job?->displayIndustry());
        $jobId = $job?->industry_id;
        $candidateDisplay = $this->display($profile?->industry?->name);
        $candidateId = $profile?->industry_id;

        if (! $this->hasRecordedValue($jobDisplay, $jobId) || ! $this->hasRecordedValue($candidateDisplay, $candidateId)) {
            $note = $application->user_id === null
                ? 'Industry is stored on the candidate profile. This guest application has no linked profile, so industry is not scored.'
                : 'Industry is compared only when both the job and the candidate profile have recorded industry data.';

            return $this->missingFactor(
                'industry',
                'Industry',
                $candidateDisplay,
                $jobDisplay,
                $note,
                $profile instanceof CandidateProfile ? 'profile' : 'none',
            );
        }

        $matched = $this->recordsMatch($candidateId, $candidateDisplay, $jobId, $jobDisplay);

        return $this->comparedFactor(
            'industry',
            'Industry',
            $candidateDisplay,
            $jobDisplay,
            $matched,
            'Compared from the current candidate profile and the job industry field.',
            'profile',
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function relocationFactor(Application $application, ?Job $job, ?CandidateProfile $profile): array
    {
        $jobDisplay = $this->display($job?->displayLocation());
        $jobId = $job?->location_id;
        $hasJobLocation = $this->hasRecordedValue($jobDisplay, $jobId);

        if (! $hasJobLocation || ! $profile instanceof CandidateProfile) {
            $note = ! $profile instanceof CandidateProfile
                ? 'Relocation preference is stored on the candidate profile. This application is not linked to a profile, so relocation is not scored.'
                : 'Relocation is compared only when the job has recorded location data and the candidate profile records a relocation preference.';

            return $this->missingFactor(
                'relocation',
                'Relocation',
                null,
                $jobDisplay,
                $note,
                $profile instanceof CandidateProfile ? 'profile' : 'none',
            );
        }

        $locationMatches = $this->locationAlreadyMatches($application, $job);
        $preferred = $profile->preferredLocations;
        $preferredNames = $preferred
            ->pluck('name')
            ->filter(fn ($name) => is_string($name) && trim($name) !== '')
            ->values();
        $candidateDisplay = $profile->willing_to_relocate
            ? ($preferredNames->isNotEmpty()
                ? 'Willing to relocate · prefers '.$preferredNames->implode(', ')
                : 'Willing to relocate')
            : 'Not willing to relocate';

        $preferredContainsJob = $preferred->contains(fn ($location) => (int) $location->id === (int) $jobId)
            || $preferredNames->contains(fn ($name) => $this->valuesMatch(
                $this->normalize($name),
                $this->normalize($jobDisplay),
            ));

        $matched = $locationMatches
            || ($profile->willing_to_relocate && ($preferred->isEmpty() || $preferredContainsJob));

        $note = $locationMatches
            ? 'Application snapshot location already matches the job location; relocation is not required.'
            : ($matched
                ? 'Candidate profile records willingness to relocate that covers the job location.'
                : 'Candidate profile does not record a relocation preference that covers the job location.');

        return $this->comparedFactor(
            'relocation',
            'Relocation',
            $candidateDisplay,
            $jobDisplay,
            $matched,
            $note,
            'profile',
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function skillsFactor(?CandidateProfile $profile): array
    {
        $candidateValue = $this->display($profile?->skillsDisplay());

        if ($candidateValue === null) {
            return $this->unavailableFactor(
                'skills',
                'Skills',
                'Job does not have structured skills data. Candidate skills are not recorded on a linked profile.',
                $candidateValue,
                null,
                $profile instanceof CandidateProfile ? 'profile' : 'none',
            );
        }

        return $this->presentedFactor(
            'skills',
            'Skills',
            $candidateValue,
            null,
            'Job does not have structured skills data. Candidate skills are shown for recruiter review only and are not scored.',
            'profile',
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function qualificationFactor(Application $application): array
    {
        $candidateValue = $this->display($application->displayQualification());

        if ($candidateValue === null) {
            return $this->missingFactor(
                'qualification',
                'Qualification',
                null,
                null,
                'Job does not have a structured qualification field. The application snapshot also has no recorded qualification.',
                'snapshot',
            );
        }

        return $this->presentedFactor(
            'qualification',
            'Qualification',
            $candidateValue,
            null,
            'Job does not have a structured qualification field. The application snapshot qualification is shown for recruiter review only and is not scored.',
            'snapshot',
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function salaryFactor(?Job $job, ?CandidateProfile $profile): array
    {
        $jobValue = $this->display($job?->salary);
        $parts = [];

        if ($profile instanceof CandidateProfile) {
            $current = $this->display($profile->formatSalary($profile->current_salary));
            $expected = $this->display($profile->formatSalary($profile->expected_salary));

            if ($current !== null) {
                $parts[] = 'Current '.$current;
            }

            if ($expected !== null) {
                $parts[] = 'Expected '.$expected;
            }
        }

        $candidateValue = $parts === [] ? null : implode(' · ', $parts);

        if ($candidateValue === null && $jobValue === null) {
            return $this->missingFactor(
                'salary',
                'Salary',
                null,
                null,
                'Job salary is unstructured free text and candidate salary is not recorded. Salary is not scored.',
                $profile instanceof CandidateProfile ? 'profile' : 'none',
            );
        }

        return $this->presentedFactor(
            'salary',
            'Salary',
            $candidateValue,
            $jobValue,
            'Job salary is unstructured free text and is not scored. Shown for recruiter review only.',
            $profile instanceof CandidateProfile ? 'profile' : 'none',
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function noticeFactor(?Job $job, ?CandidateProfile $profile): array
    {
        $candidateValue = $this->display($profile?->notice_period?->label());
        $jobValue = $job?->joining_date?->toDateString();

        if ($candidateValue === null && $jobValue === null) {
            return $this->missingFactor(
                'notice',
                'Notice period',
                null,
                null,
                'Job does not have a structured notice-period field and candidate notice period is not recorded. This factor is not scored.',
                $profile instanceof CandidateProfile ? 'profile' : 'none',
            );
        }

        return $this->presentedFactor(
            'notice',
            'Notice period',
            $candidateValue,
            $jobValue !== null ? 'Joining '.$jobValue : null,
            'Job does not have a structured notice-period field. Candidate notice period and job joining date are shown for recruiter review only and are not scored.',
            $profile instanceof CandidateProfile ? 'profile' : 'none',
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function designationFactor(?Job $job, ?CandidateProfile $profile): array
    {
        $candidateValue = $this->display($profile?->current_designation);
        $jobValue = $this->display($job?->title);

        if ($candidateValue === null && $jobValue === null) {
            return $this->missingFactor(
                'designation',
                'Designation',
                null,
                null,
                'Job title and candidate designation are not recorded. Designation is not scored.',
                $profile instanceof CandidateProfile ? 'profile' : 'none',
            );
        }

        return $this->presentedFactor(
            'designation',
            'Designation',
            $candidateValue,
            $jobValue,
            'Job title and candidate designation are shown for recruiter review only and are not scored.',
            $profile instanceof CandidateProfile ? 'profile' : 'none',
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function industryExperienceFactor(?Job $job, ?CandidateProfile $profile, Application $application): array
    {
        if (! $profile instanceof CandidateProfile) {
            return $this->missingFactor(
                'industry_experience',
                'Industry experience',
                null,
                $this->display($job?->displayIndustry()),
                $application->user_id === null
                    ? 'Employment history is stored on the candidate profile. This guest application has no linked profile.'
                    : 'Employment history is not recorded on a linked candidate profile.',
                'none',
            );
        }

        $histories = $profile->employmentHistories;
        $jobIndustry = $this->display($job?->displayIndustry());
        $jobIndustryId = $job?->industry_id;

        if ($histories->isEmpty()) {
            return $this->missingFactor(
                'industry_experience',
                'Industry experience',
                null,
                $jobIndustry,
                'Employment history is not recorded on the candidate profile. This factor is not scored.',
                'profile',
            );
        }

        $matching = $histories->filter(function ($history) use ($jobIndustryId, $jobIndustry) {
            if ($jobIndustryId && (int) $history->industry_id === (int) $jobIndustryId) {
                return true;
            }

            return $this->valuesMatch(
                $this->normalize($history->industry?->name),
                $this->normalize($jobIndustry),
            );
        })->count();

        $candidateValue = $histories->count().' role(s) recorded';
        if ($this->hasRecordedValue($jobIndustry, $jobIndustryId)) {
            $candidateValue .= ' · '.$matching.' in recorded job industry';
        }

        return $this->presentedFactor(
            'industry_experience',
            'Industry experience',
            $candidateValue,
            $jobIndustry,
            'Employment history is shown for recruiter review only. Years and roles are not scored.',
            'profile',
        );
    }

    private function locationAlreadyMatches(Application $application, ?Job $job): bool
    {
        $candidateDisplay = $this->display($application->displayLocation());
        $jobDisplay = $this->display($job?->displayLocation());

        if (! $this->hasRecordedValue($candidateDisplay, $application->location_id)
            || ! $this->hasRecordedValue($jobDisplay, $job?->location_id)) {
            return false;
        }

        return $this->recordsMatch(
            $application->location_id,
            $candidateDisplay,
            $job?->location_id,
            $jobDisplay,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function unavailableFactor(
        string $key,
        string $label,
        string $note,
        ?string $candidateValue = null,
        ?string $jobValue = null,
        string $source = 'none',
    ): array {
        return [
            'key' => $key,
            'label' => $label,
            'state' => 'unavailable',
            'matched' => null,
            'contributed' => false,
            'candidate_value' => $candidateValue,
            'job_value' => $jobValue,
            'note' => $note,
            'source' => $source,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function presentedFactor(
        string $key,
        string $label,
        ?string $candidateValue,
        ?string $jobValue,
        string $note,
        string $source,
    ): array {
        return [
            'key' => $key,
            'label' => $label,
            'state' => 'presented',
            'matched' => null,
            'contributed' => false,
            'candidate_value' => $candidateValue,
            'job_value' => $jobValue,
            'note' => $note,
            'source' => $source,
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
        string $source = 'none',
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
            'source' => $source,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function comparedFactor(
        string $key,
        string $label,
        ?string $candidateValue,
        ?string $jobValue,
        bool $matched,
        string $note,
        string $source,
    ): array {
        return [
            'key' => $key,
            'label' => $label,
            'state' => 'compared',
            'matched' => $matched,
            'contributed' => $matched,
            'candidate_value' => $candidateValue,
            'job_value' => $jobValue,
            'note' => $note,
            'source' => $source,
        ];
    }

    private function hasRecordedValue(?string $display, ?int $id): bool
    {
        return $display !== null || ($id !== null && $id > 0);
    }

    private function recordsMatch(?int $candidateId, ?string $candidateName, ?int $jobId, ?string $jobName): bool
    {
        if ($candidateId && $jobId && $candidateId === $jobId) {
            return true;
        }

        return $this->valuesMatch($this->normalize($candidateName), $this->normalize($jobName));
    }

    private function valuesMatch(?string $candidate, ?string $job): bool
    {
        if ($candidate === null || $job === null) {
            return false;
        }

        if ($candidate === $job) {
            return true;
        }

        return str_contains($candidate, $job) || str_contains($job, $candidate);
    }

    private function display(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function normalize(?string $value): ?string
    {
        $value = strtolower(trim((string) $value));

        return $value === '' ? null : $value;
    }
}
