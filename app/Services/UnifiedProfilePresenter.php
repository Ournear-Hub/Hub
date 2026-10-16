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
use App\Support\PublicShareUrlBuilder;

class UnifiedProfilePresenter
{
    public function __construct(
        private UserPhotoService $photos,
        private PublicShareUrlBuilder $shareUrls,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function publicCandidate(User $user, bool $forOwner = false): array
    {
        $user->loadMissing([
            'candidateProfile.industry',
            'candidateProfile.jobCategory',
            'candidateProfile.classifiedExperienceRange',
            'candidateProfile.skills',
            'candidateProfile.employmentHistories.industry',
            'candidateProfile.certifications',
            'candidateProfile.languages',
            'candidateProfile.educations',
            'candidateProfile.projects',
        ]);

        $profile = $user->candidateProfile;
        if ($forOwner) {
            abort_unless($profile instanceof CandidateProfile, 404);
        } else {
            abort_unless($profile instanceof CandidateProfile && $profile->shareAllows('enabled'), 404);
        }

        $allows = fn (string $field): bool => $forOwner || $profile->shareAllows($field);

        $shareRole = $allows('current_role');
        $headline = $allows('headline')
            ? $this->headline($user, $profile)
            : ($shareRole ? $profile->current_designation : null);

        $experienceLabel = null;
        if ($shareRole) {
            $experienceLabel = $profile->classifiedExperienceRange?->name ?: $profile->experience;
            if (! filled($experienceLabel)) {
                $experienceLabel = null;
            }
        }

        $payload = [
            'name' => $user->name,
            'username' => $user->username,
            'photo_url' => $this->photos->publicUrl($user->photo_path),
            'headline' => $headline,
            'location' => $allows('location') ? ($user->location ?: $profile->location) : null,
            'bio' => $allows('bio') ? $user->bio : null,
            'designation' => $shareRole ? $profile->current_designation : null,
            'current_company' => $shareRole ? $profile->current_company : null,
            'industry' => $shareRole ? $profile->industry?->name : null,
            'functional_area' => $shareRole ? $profile->jobCategory?->name : null,
            'experience' => $experienceLabel,
            'qualification' => $shareRole ? $profile->qualification : null,
            'skills' => $allows('skills')
                ? $profile->skills->pluck('name')->filter()->values()->all()
                : [],
            'employment_history' => $allows('history')
                ? $profile->employmentHistories
                    ->map(fn ($history) => [
                        'id' => $history->id,
                        'company' => $history->company,
                        'designation' => $history->designation,
                        'industry' => $history->industry?->name,
                        'started_on' => $history->started_on?->toDateString(),
                        'ended_on' => $history->ended_on?->toDateString(),
                        'is_current' => (bool) $history->is_current,
                        'is_internship' => (bool) $history->is_internship,
                        'description' => $history->description,
                    ])
                    ->values()
                    ->all()
                : [],
            'educations' => $allows('education')
                ? $profile->educations->map(fn (CandidateEducation $education) => [
                    'institution' => $education->institution,
                    'qualification' => $education->qualification,
                    'field' => $education->field,
                    'started_on' => $education->started_on?->toDateString(),
                    'ended_on' => $education->ended_on?->toDateString(),
                ])->values()->all()
                : [],
            'projects' => $allows('projects')
                ? $profile->projects->map(fn (CandidateProject $project) => [
                    'name' => $project->name,
                    'role' => $project->role,
                    'company' => $project->company,
                    'description' => $project->description,
                    'started_on' => $project->started_on?->toDateString(),
                    'ended_on' => $project->ended_on?->toDateString(),
                ])->values()->all()
                : [],
            'certifications' => $allows('certifications')
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
            'languages' => $allows('languages')
                ? $profile->languages
                    ->map(fn (CandidateLanguage $language) => [
                        'name' => $language->name,
                        'proficiency' => $language->toAccountArray()['proficiency_label'],
                    ])
                    ->values()
                    ->all()
                : [],
            'share_enabled' => $profile->shareAllows('enabled'),
        ];

        if ($profile->shareAllows('enabled')) {
            $payload['share_url'] = $this->shareUrls->forProfile($user);
        }

        if ($forOwner) {
            $payload['verifications'] = [
                'email' => $user->emailVerified(),
                'mobile' => $user->mobileVerified(),
                'whatsapp' => $user->whatsappVerified(),
            ];
        }

        return $payload;
    }

    /**
     * @return array<string, mixed>
     */
    public function publicCompany(EmployerProfile $employer): array
    {
        $employer->loadMissing(['user', 'locations', 'classifiedIndustry']);
        abort_unless($employer->user?->is_active === true && $employer->shareAllows('enabled'), 404);

        return $this->sharedPublicCompanyFields($employer, includeVerificationFlag: true);
    }

    /**
     * @return array<string, mixed>
     */
    private function sharedPublicCompanyFields(EmployerProfile $employer, bool $includeVerificationFlag): array
    {
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

        $displayName = trim($employer->publicDisplayCompany());
        if ($displayName === '') {
            $displayName = trim((string) $employer->company_name);
        }
        if ($displayName === '') {
            $displayName = 'Company';
        }

        $payload = [
            'id' => $employer->id,
            'business_id' => $employer->business_id,
            'company_name' => $displayName,
            'logo_url' => $employer->logoUrl(false),
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
            'public_jobs_url' => route('jobs.index', ['employer' => $employer->id]),
            'share_url' => $this->shareUrls->forCompany($employer),
        ];

        if ($includeVerificationFlag && $employer->ournearVerified()) {
            $payload['ournear_verified'] = true;
        }

        return $payload;
    }

    /**
     * Company payload for the public job detail "Company" tab (does not abort when sharing is disabled).
     *
     * @return array<string, mixed>|null
     */
    public function publicCompanyForJobTab(?EmployerProfile $employer): ?array
    {
        if (! $employer instanceof EmployerProfile) {
            return null;
        }

        $employer->loadMissing(['user', 'locations', 'classifiedIndustry']);
        if ($employer->user?->is_active !== true) {
            return null;
        }

        $verified = $employer->ournearVerified();
        if (! $employer->shareAllows('enabled')) {
            $displayName = trim($employer->publicDisplayCompany());
            if ($displayName === '') {
                $displayName = trim((string) $employer->company_name);
            }

            return [
                'id' => $employer->id,
                'company_name' => $displayName !== '' ? $displayName : 'Company',
                'logo_url' => $employer->logoUrl(false),
                'profile_available' => false,
                'public_profile_url' => null,
            ] + ($verified ? ['company_verified' => true] : []);
        }

        return [
            ...$this->sharedPublicCompanyFields($employer, includeVerificationFlag: false),
            'profile_available' => true,
            'public_profile_url' => route('companies.show', $employer, false),
        ] + ($verified ? ['company_verified' => true] : []);
    }

    /**
     * @return array<string, mixed>
     */
    public function ownerIdentity(User $user): array
    {
        $candidate = $user->candidateProfile;
        $employer = $user->employerProfile;
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
            'public_profile_url' => filled($user->username)
                ? route('profiles.show', ['user' => $user->username], false)
                : null,
            'public_profile_share_enabled' => $candidate instanceof CandidateProfile && $candidate->shareAllows('enabled'),
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
