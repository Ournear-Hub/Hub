<?php

namespace App\Services;

use App\Models\CandidateProfile;
use App\Models\User;

class ResumeBuilderService
{
    /**
     * @return array<string, mixed>
     */
    public function preview(User $user): array
    {
        $profile = $user->candidateProfile;
        $professional = $profile instanceof CandidateProfile
            ? $profile->toProfessionalArray()
            : [];

        unset($professional['current_salary'], $professional['expected_salary']);

        return [
            'personal' => [
                'name' => $user->name,
                'email' => $user->email,
                'mobile' => $user->mobile ?: ($profile?->mobile),
                'location' => $user->location ?: ($profile?->location),
                'bio' => $user->bio,
                'username' => $user->username,
            ],
            'professional' => $professional,
            'primary_resume' => $profile?->resumes()
                ->where('is_primary', true)
                ->first()
                ?->toAccountArray(),
            'note' => 'This preview uses your existing profile. It does not create a second profile record. PDF export can be added later.',
        ];
    }
}
