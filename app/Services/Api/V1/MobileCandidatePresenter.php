<?php

namespace App\Services\Api\V1;

use App\Models\Application;
use App\Models\CandidateProfile;
use App\Models\CandidateResume;
use App\Models\User;
use App\Services\CandidateApplicationViewService;
use App\Services\ResumeBuilderService;
use App\Services\UserPhotoService;
use App\Support\OurNearHireIdentity;

class MobileCandidatePresenter
{
    public function __construct(
        private UserPhotoService $photos,
        private ResumeBuilderService $resumeBuilder,
        private CandidateApplicationViewService $applications,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function account(User $user): array
    {
        $user->loadMissing(['candidateProfile']);

        return [
            'name' => $user->name,
            'username' => $user->username,
            'email' => $user->email,
            'role' => $user->role->value,
            'mobile' => $user->mobile,
            'location' => $user->location,
            'bio' => $user->bio,
            'professional_headline' => $user->professional_headline,
            'preferred_language' => $user->preferred_language?->value,
            'photo_url' => $this->photos->publicUrl($user->photo_path),
            'ournear_id' => app(OurNearHireIdentity::class)->display($user),
            'profile_completion' => (int) $user->profile_completion,
            'email_verified' => $user->hasVerifiedEmail(),
            'mobile_verified' => $user->mobileVerified(),
            'must_change_password' => (bool) $user->must_change_password,
            'completion_items' => $user->profileCompletionItems(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function personal(User $user): array
    {
        $profile = $user->candidateProfile;

        return [
            'name' => $user->name,
            'username' => $user->username,
            'email' => $user->email,
            'mobile' => $user->mobile,
            'location' => $user->location,
            'bio' => $user->bio,
            'professional_headline' => $user->professional_headline,
            'preferred_language' => $user->preferred_language?->value,
            'photo_url' => $this->photos->publicUrl($user->photo_path),
            'qualification' => $profile?->qualification,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function professional(CandidateProfile $profile): array
    {
        $data = $profile->toProfessionalArray();
        unset($data['public_share']);

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    public function resume(CandidateResume $resume): array
    {
        $data = $resume->toAccountArray();
        $data['download_url'] = route('api.v1.account.resumes.download', $resume);
        $data['view_url'] = route('api.v1.account.resumes.view', $resume);

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    public function resumeBuilder(User $user): array
    {
        return [
            'preview' => $this->resumeBuilder->preview($user),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function applicationListItem(Application $application): array
    {
        $item = $this->applications->listItem($application);
        unset($item['href']);
        if (isset($item['resume_url'])) {
            $item['resume_url'] = route('api.v1.account.applications.resume', $application);
        }

        return $item;
    }

    /**
     * @return array<string, mixed>
     */
    public function applicationDetail(Application $application): array
    {
        $detail = $this->applications->detail($application);
        if (isset($detail['resume']['download_url'])) {
            $detail['resume']['download_url'] = route('api.v1.account.applications.resume', $application);
        }

        return $detail;
    }
}
