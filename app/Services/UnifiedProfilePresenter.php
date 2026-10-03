<?php

namespace App\Services;

use App\Models\CandidateCertification;
use App\Models\CandidateEducation;
use App\Models\CandidateLanguage;
use App\Models\CandidateProfile;
use App\Models\CandidateProject;
use App\Models\EmployerProfile;
use App\Models\Job;
use App\Models\User;
use App\Services\UserPhotoService;

class UnifiedProfilePresenter
{
    public function __construct(private UserPhotoService $photos) {}

    /**
     * @return array<string, mixed>
     */
    public function publicCandidate(User $user): array
    {
        $user->loadMissing([
            'candidateProfile.industry',
            'candidateProfile.jobCategory',
            'candidateProfile.skills',
            'candidateProfile.employmentHistories.industry',
            'candidateProfile.certifications',
            'candidateProfile.languages',
            'candidateProfile.educations',
            'candidateProfile.projects',
        ]);

        $profile = $user->candidateProfile;
        abort_unless($profile instanceof CandidateProfile && $profile->shareAllows('enabled'), 404);

        $shareRole = $profile->shareAllows('current_role');
        $headline = $profile->shareAllows('headline')
            ? $this->headline($user, $profile)
            : ($shareRole ? $profile->current_designation : null);

        return [
            'name' => $user->name,
            'username' => $user->username,
            'business_id' => $user->business_id,
            'photo_url' => $this->photos->publicUrl($user->photo_path),
            'headline' => $headline,
            'location' => $profile->shareAllows('location') ? ($user->location ?: $profile->location) : null,
            'bio' => $profile->shareAllows('bio') ? $user->bio : null,
            'designation' => $shareRole ? $profile->current_designation : null,
            'current_company' => $shareRole ? $profile->current_company : null,
            'industry' => $shareRole ? $profile->industry?->name : null,
            'functional_area' => $shareRole ? $profile->jobCategory?->name : null,
            'experience' => $shareRole ? $profile->experience : null,
            'qualification' => $shareRole ? $profile->qualification : null,
            'verifications' => [
                'email' => $user->emailVerified(),
                'mobile' => $user->mobileVerified(),
                'whatsapp' => $user->whatsappVerified(),
            ],
            'skills' => $profile->shareAllows('skills')
                ? $profile->skills->pluck('name')->filter()->values()->all()
                : [],
            'employment_history' => $profile->shareAllows('history')
                ? $profile->employmentHistories
                    ->map(fn ($history) => [
                        'job_title' => $history->designation,
                        'company_name' => $history->company,
                        'industry' => $history->industry?->name,
                        'is_current' => $history->is_current,
                    ])
                    ->values()
                    ->all()
                : [],
            'educations' => $profile->shareAllows('education')
                ? $profile->educations->map(fn (CandidateEducation $education) => [
                    'institution' => $education->institution,
                    'qualification' => $education->qualification,
                    'field' => $education->field,
                    'started_on' => $education->started_on?->toDateString(),
                    'ended_on' => $education->ended_on?->toDateString(),
                ])->values()->all()
                : [],
            'projects' => $profile->shareAllows('projects')
                ? $profile->projects->map(fn (CandidateProject $project) => [
                    'name' => $project->name,
                    'role' => $project->role,
                    'company' => $project->company,
                    'description' => $project->description,
                    'started_on' => $project->started_on?->toDateString(),
                    'ended_on' => $project->ended_on?->toDateString(),
                ])->values()->all()
                : [],
            'certifications' => $profile->shareAllows('certifications')
                ? $profile->certifications
                    ->map(fn (CandidateCertification $certification) => [
                        'name' => $certification->name,
                        'issuer' => $certification->issuer,
                        'issued_on' => $certification->issued_on?->toDateString(),
                        'expires_on' => $certification->expires_on?->toDateString(),
                    ])
                    ->values()
                    ->all()
                : [],
            'languages' => $profile->shareAllows('languages')
                ? $profile->languages
                    ->map(fn (CandidateLanguage $language) => [
                        'name' => $language->name,
                        'proficiency' => $language->toAccountArray()['proficiency_label'],
                    ])
                    ->values()
                    ->all()
                : [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function publicCompany(EmployerProfile $employer): array
    {
        $employer->loadMissing(['user', 'locations', 'classifiedIndustry']);
        abort_unless($employer->user?->is_active === true && $employer->shareAllows('enabled'), 404);

        $socials = $employer->shareAllows('socials')
            ? array_filter($employer->socialLinks(), fn ($url) => filled($url))
            : [];

        $jobs = Job::query()
            ->where('employer_id', $employer->id)
            ->publiclyVisible()
            ->latest('posted_date')
            ->limit(8)
            ->get()
            ->map(fn (Job $job) => [
                'title' => $job->title,
                'slug' => $job->slug,
                'location' => $job->location,
                'employment_type' => $job->employment_type,
            ])
            ->values()
            ->all();

        return [
            'id' => $employer->id,
            'business_id' => $employer->business_id,
            'company_name' => $employer->publicDisplayCompany(),
            'logo_url' => $this->photos->publicUrl($employer->user?->photo_path),
            'ournear_verified' => $employer->ournearVerified(),
            'about_company' => $employer->shareAllows('about') ? $employer->about_company : null,
            'company_size' => $employer->shareAllows('industry_size') ? $employer->company_size : null,
            'industry' => $employer->shareAllows('industry_size') ? ($employer->displayIndustry() ?: null) : null,
            'company_details' => $employer->shareAllows('details') ? $employer->company_details : null,
            'company_website' => $employer->shareAllows('website') ? $employer->company_website : null,
            'locations' => $employer->shareAllows('locations')
                ? $employer->locations->pluck('name')->filter()->values()->all()
                : [],
            'benefits' => $employer->shareAllows('benefits') ? $employer->benefitList() : [],
            'socials' => $socials,
            'open_jobs' => $jobs,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function ownerIdentity(User $user): array
    {
        $candidate = $user->candidateProfile;
        $employer = $user->employerProfile;
        $publicEnabled = $candidate instanceof CandidateProfile && $candidate->shareAllows('enabled');

        $mobileRequired = $user->isEmployer() || $employer instanceof EmployerProfile;

        $hiringLinks = [];
        if ($user->isCandidate()) {
            $hiringLinks = [
                ['label' => 'Applications', 'href' => '/account/applications'],
                ['label' => 'Saved jobs', 'href' => '/account/saved-jobs'],
                ['label' => 'Resume library', 'href' => '/account/resumes'],
            ];
            if ($user->canAccessHiringWorkspace()) {
                array_unshift($hiringLinks, ['label' => 'Hiring dashboard', 'href' => '/account/hiring']);
            }
        } elseif ($user->isEmployer()) {
            $hiringLinks = [
                ['label' => 'Jobs', 'href' => '/account/jobs'],
                ['label' => 'Submissions', 'href' => '/account/submissions'],
                ['label' => 'Enquiries', 'href' => '/account/enquiries'],
            ];
        }

        return [
            'professional_headline' => $user->professional_headline,
            'ournear_id' => app(\App\Support\OurNearHireIdentity::class)->display($user),
            'headline' => $this->headline($user, $candidate),
            'verifications' => [
                'email' => $user->emailVerified(),
                'mobile' => $user->mobileVerified(),
                'whatsapp' => $user->whatsappVerified(),
            ],
            'verification_context' => [
                'mobile_required' => $mobileRequired,
            ],
            'public_profile_url' => $publicEnabled && filled($user->username)
                ? route('profiles.show', ['user' => $user->username], false)
                : null,
            'modules' => [
                'identity' => $user->business_id,
                'candidate' => $candidate?->business_id,
                'company' => $employer?->business_id,
                'company_name' => $employer?->displayCompany(),
            ],
            'hiring_links' => $hiringLinks,
        ];
    }

    private function headline(User $user, ?CandidateProfile $profile): ?string
    {
        if (filled($user->professional_headline)) {
            return $user->professional_headline;
        }

        $parts = array_filter([
            $profile?->current_designation,
            $profile?->current_company,
        ]);

        return $parts === [] ? null : implode(' | ', $parts);
    }
}
