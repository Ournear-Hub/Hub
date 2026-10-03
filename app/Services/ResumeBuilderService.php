<?php

namespace App\Services;

use App\Models\CandidateProfile;
use App\Models\User;

class ResumeBuilderService
{
    /**
     * Live projection of the candidate profile for the OurNear resume preview.
     * This does not create a second profile or resume record.
     *
     * @return array<string, mixed>
     */
    public function preview(User $user): array
    {
        $user->loadMissing([
            'candidateProfile.classifiedLocation',
            'candidateProfile.classifiedExperienceRange',
            'candidateProfile.classifiedQualification',
            'candidateProfile.industry',
            'candidateProfile.jobCategory',
            'candidateProfile.skills',
            'candidateProfile.employmentHistories.industry',
            'candidateProfile.preferredLocations',
            'candidateProfile.preferredEmploymentTypes',
            'candidateProfile.certifications',
            'candidateProfile.languages',
        ]);

        $profile = $user->candidateProfile;
        $professional = $profile instanceof CandidateProfile
            ? $profile->toProfessionalArray()
            : [];

        unset(
            $professional['current_salary'],
            $professional['expected_salary'],
            $professional['public_share'],
        );

        if ($profile instanceof CandidateProfile) {
            $classifiedExperience = $this->display($profile->classifiedExperienceRange?->name);
            $classifiedQualification = $this->display($profile->classifiedQualification?->name);

            if ($classifiedExperience !== null) {
                $professional['experience'] = $classifiedExperience;
            }

            if ($classifiedQualification !== null) {
                $professional['qualification'] = $classifiedQualification;
            }

            $professional['relocation_summary'] = $this->relocationSummary($profile);
        }

        $location = $this->display($profile?->classifiedLocation?->name)
            ?? $this->display($user->location)
            ?? $this->display($profile?->location);

        return [
            'personal' => [
                'name' => $this->display($user->name),
                'email' => $this->display($user->email),
                'mobile' => $this->display($user->mobile) ?? $this->display($profile?->mobile),
                'location' => $location,
                'bio' => $this->display($user->bio),
                'designation' => $this->display($profile?->current_designation),
                'photo_url' => $user->photoUrl(),
                'user_business_id' => $this->display($user->business_id),
                'candidate_business_id' => $this->display($profile?->business_id),
                'ournear_id' => app(\App\Support\OurNearHireIdentity::class)->display($user, 'CAN'),
            ],
            'professional' => $professional,
            'primary_resume' => $profile?->resumes()
                ->where('is_primary', true)
                ->first()
                ?->toAccountArray(),
            'note' => 'This OurNear resume is a live preview of your existing profile. Print from your browser. It does not create a second profile or change application resume snapshots.',
        ];
    }

    private function relocationSummary(CandidateProfile $profile): ?string
    {
        $preferred = $profile->preferredLocations
            ->pluck('name')
            ->filter(fn ($name) => is_string($name) && trim($name) !== '')
            ->implode(', ');

        if ($profile->willing_to_relocate) {
            return $preferred !== ''
                ? 'Willing to relocate · prefers '.$preferred
                : 'Willing to relocate';
        }

        return $preferred !== ''
            ? 'Not willing to relocate · recorded preferences: '.$preferred
            : null;
    }

    private function display(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
