<?php

namespace App\Http\Controllers;

use App\Models\EmployerProfile;
use App\Models\User;
use App\Services\UserPhotoService;
use App\Support\PublicContent;
use Inertia\Inertia;
use Inertia\Response;

class PublicProfileController extends Controller
{
    public function candidate(User $user): Response
    {
        abort_unless($user->isCandidate() && $user->is_active, 404);
        $user->loadMissing([
            'candidateProfile.industry',
            'candidateProfile.jobCategory',
            'candidateProfile.skills',
            'candidateProfile.employmentHistories.industry',
        ]);
        $profile = $user->candidateProfile;

        return Inertia::render('Profiles/Show', [
            'content' => PublicContent::common(),
            'profile' => [
                'name' => $user->name,
                'username' => $user->username,
                'photo_url' => app(UserPhotoService::class)->publicUrl($user->photo_path),
                'bio' => $user->bio,
                'designation' => $profile?->current_designation,
                'current_company' => $profile?->current_company,
                'industry' => $profile?->industry?->name,
                'functional_area' => $profile?->jobCategory?->name,
                'experience' => $profile?->experience,
                'qualification' => $profile?->qualification,
                'skills' => $profile?->skills->pluck('name')->filter()->values()->all() ?? [],
                'employment_history' => $profile?->employmentHistories
                    ->map(fn ($history) => [
                        'job_title' => $history->designation,
                        'company_name' => $history->company,
                        'industry' => $history->industry?->name,
                        'is_current' => $history->is_current,
                    ])
                    ->values()
                    ->all() ?? [],
            ],
        ]);
    }

    public function company(EmployerProfile $employer): Response
    {
        $employer->loadMissing('user');
        abort_unless($employer->user?->is_active === true, 404);

        return Inertia::render('Companies/Show', [
            'content' => PublicContent::common(),
            'company' => [
                'business_id' => $employer->business_id,
                'company_name' => $employer->displayCompany(),
                'logo_url' => app(UserPhotoService::class)->publicUrl($employer->user?->photo_path),
                'about_company' => $employer->about_company,
                'company_size' => $employer->company_size,
                'industry' => $employer->displayIndustry() ?: null,
                'company_details' => $employer->company_details,
                'company_website' => $employer->company_website,
            ],
        ]);
    }
}
