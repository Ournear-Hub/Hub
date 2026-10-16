<?php

namespace App\Services;

use App\Enums\ApplicationStatus;
use App\Enums\DocumentType;
use App\Enums\DocumentVerificationStatus;
use App\Models\Application;
use App\Models\CandidateDocument;
use App\Models\CandidateResume;
use App\Enums\EmployerEnquiryStatus;
use App\Models\EmployerEnquiry;
use App\Models\EmployerProfile;
use App\Models\EmployerSubmission;
use App\Models\Job;
use App\Models\SavedJob;
use App\Models\User;
use App\Enums\FileLifecycleStatus;
use App\Support\AtsStage;
use App\Support\CandidateProfileGuidance;
use App\Support\EmployerApplicationPipeline;
use App\Support\EmployerVisibleCandidate;
use App\Support\JobListingQuery;

class AccountDashboardService
{
    public function __construct(
        private EmployerPostingEligibilityService $employerEligibility,
        private JobListingQuery $jobListings,
    ) {}

    /**
     * @return array{role: string, profile: array<string, mixed>, dashboard: ?array<string, mixed>}
     */
    /**
     * Lightweight personal sidebar identity (all candidate account pages).
     *
     * @return array<string, mixed>|null
     */
    public function candidateSidebarProfile(User $user): ?array
    {
        if (! $user->isCandidate()) {
            return null;
        }

        $user->loadMissing('candidateProfile');
        $candidateProfile = $user->candidateProfile;

        $organization = trim((string) ($candidateProfile?->current_company ?? ''));
        $designation = trim((string) ($candidateProfile?->current_designation ?? ''));
        $headline = trim((string) ($user->professional_headline ?? ''));
        $location = trim((string) ($user->location ?? ''));
        $username = trim((string) ($user->username ?? ''));

        return [
            'name' => $user->name,
            'username' => $username !== '' ? $username : null,
            'photo_url' => $user->photoUrl(),
            'designation' => $designation !== '' ? $designation : null,
            'professional_headline' => $headline !== '' ? $headline : null,
            'location' => $location !== '' ? $location : null,
            'organization' => $organization !== '' ? $organization : null,
        ];
    }

    public function home(User $user): array
    {
        $user->loadMissing(['candidateProfile', 'employerProfile']);

        $profile = $user->toAccountProfileArray();
        if ($user->isCandidate()) {
            $profile = $this->enrichCandidateHomeProfile($user, $profile);
        } elseif ($user->isEmployer()) {
            $profile = array_merge($profile, $user->employerProfile?->toAccountArray() ?? []);
        }

        return [
            'role' => $user->role->value,
            'profile' => $profile,
            'dashboard' => $user->isCandidate()
                ? $this->candidateDashboard($user)
                : null,
        ];
    }

    /**
     * Employer hiring workspace payload for `/account/hiring` when services are active.
     *
     * @return array<string, mixed>
     */
    public function employerHiringCommandCenter(User $user): array
    {
        $user->loadMissing('employerProfile');
        $dashboard = $this->employerDashboard($user);
        $profile = $user->employerProfile;

        $activeJobs = [];
        $recentJobs = [];
        $pipelineHiringStages = [];

        if ($profile instanceof EmployerProfile) {
            $activeJobs = $profile->jobs()
                ->where('active', true)
                ->withCount('applications')
                ->orderByDesc('updated_at')
                ->limit(5)
                ->get()
                ->map(fn (Job $job) => self::employerWorkspaceJobRow($job))
                ->values()
                ->all();

            $recentJobs = $profile->jobs()
                ->withCount('applications')
                ->orderByDesc('updated_at')
                ->limit(5)
                ->get()
                ->map(fn (Job $job) => self::employerWorkspaceJobRow($job))
                ->values()
                ->all();

            $pipelineCounts = collect(EmployerApplicationPipeline::summaryForEmployerApplicationsAts($profile))
                ->keyBy('key');

            $stageCount = static fn (string $key): int => (int) ($pipelineCounts->get($key)['count'] ?? 0);

            $pipelineHiringStages = [
                [
                    'key' => AtsStage::Applied,
                    'label' => 'Applications',
                    'count' => $stageCount(AtsStage::Applied),
                    'href' => '/account/hiring/applications',
                ],
                [
                    'key' => AtsStage::Screening,
                    'label' => 'Screening',
                    'count' => $stageCount(AtsStage::Screening),
                    'href' => '/account/hiring/applications?stage='.AtsStage::Screening,
                ],
                [
                    'key' => AtsStage::Shortlisted,
                    'label' => 'Shortlisted',
                    'count' => $stageCount(AtsStage::Shortlisted),
                    'href' => '/account/hiring/applications?stage='.AtsStage::Shortlisted,
                ],
                [
                    'key' => AtsStage::Interview,
                    'label' => 'Interviewed',
                    'count' => $stageCount(AtsStage::Interview),
                    'href' => '/account/hiring/applications?stage='.AtsStage::Interview,
                ],
                [
                    'key' => AtsStage::Selected,
                    'label' => 'Selected',
                    'count' => $stageCount(AtsStage::Selected),
                    'href' => '/account/hiring/applications?stage='.AtsStage::Selected,
                ],
                [
                    'key' => AtsStage::Offer,
                    'label' => 'Offer',
                    'count' => $stageCount(AtsStage::Offer),
                    'href' => '/account/hiring/applications?stage='.AtsStage::Offer,
                ],
                [
                    'key' => AtsStage::Joined,
                    'label' => 'Joined',
                    'count' => $stageCount(AtsStage::Joined),
                    'href' => '/account/hiring/applications?stage='.AtsStage::Joined,
                ],
            ];
        }

        $dashboard['user_name'] = $user->name;
        $dashboard['actions_required'] = array_values(array_filter(
            $dashboard['next_actions'],
            fn (array $action) => ($action['kind'] ?? '') === 'pending',
        ));
        $dashboard['active_jobs'] = $activeJobs;
        $dashboard['recent_jobs'] = $recentJobs;
        $dashboard['pipeline_hiring_stages'] = $pipelineHiringStages;
        $dashboard['pending_actions_count'] = count($dashboard['actions_required']);
        $dashboard['can_post_job'] = ($dashboard['eligibility']['eligible'] ?? false)
            && ! ($dashboard['posting_restricted'] ?? false);

        if ($profile instanceof EmployerProfile) {
            $talentSummary = collect(EmployerApplicationPipeline::summaryForEmployer($profile))->keyBy('key');
            $dashboard['talent_pool_summary'] = [
                'total' => (int) ($talentSummary->get('all')['count'] ?? 0),
                'stages' => collect(EmployerApplicationPipeline::talentPoolStages())
                    ->filter(fn (array $stage) => $stage['key'] !== 'all')
                    ->map(fn (array $stage) => [
                        'key' => $stage['key'],
                        'label' => $stage['label'],
                        'count' => (int) ($talentSummary->get($stage['key'])['count'] ?? 0),
                        'href' => $stage['key'] === 'all'
                            ? '/account/hiring/candidates'
                            : '/account/hiring/candidates?stage='.$stage['key'],
                    ])
                    ->values()
                    ->all(),
                'href' => '/account/hiring/candidates',
            ];

            $enquiryRows = EmployerEnquiry::query()
                ->where('user_id', $user->id)
                ->selectRaw('status, COUNT(*) as aggregate')
                ->groupBy('status')
                ->get();

            $submitted = 0;
            $inProgress = 0;
            $completed = 0;

            foreach ($enquiryRows as $row) {
                $status = $row->status instanceof EmployerEnquiryStatus
                    ? $row->status
                    : EmployerEnquiryStatus::tryFrom((string) $row->status);
                if (! $status instanceof EmployerEnquiryStatus) {
                    continue;
                }

                $count = (int) $row->aggregate;
                $submitted += $count;
                if (in_array($status, [EmployerEnquiryStatus::Contacted, EmployerEnquiryStatus::InProgress], true)) {
                    $inProgress += $count;
                }
                if ($status === EmployerEnquiryStatus::Completed) {
                    $completed += $count;
                }
            }

            $dashboard['staffing_summary'] = [
                'submitted' => $submitted,
                'in_progress' => $inProgress,
                'completed' => $completed,
                'href' => '/account/enquiries',
            ];
        }

        return $dashboard;
    }

    /**
     * @return array{
     *     title: string,
     *     location: string|null,
     *     applications_count: int,
     *     status_label: string,
     *     updated_at: string|null,
     *     slug: string,
     *     href: string,
     *     edit_href: string
     * }
     */
    private static function employerWorkspaceJobRow(Job $job): array
    {
        return [
            'title' => $job->title,
            'slug' => $job->slug,
            'location' => $job->displayLocation(),
            'applications_count' => (int) $job->applications_count,
            'status_label' => $job->statusLabel(),
            'updated_at' => $job->updated_at?->toDateTimeString(),
            'href' => '/account/jobs/'.$job->slug.'/applicants',
            'edit_href' => route('account.employer.jobs.edit', $job, false),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function employerDashboard(User $user): array
    {
        $profile = $user->employerProfile;
        $jobs = $profile?->jobs();
        $jobsCount = $jobs ? (clone $jobs)->count() : 0;
        $activeJobsCount = $jobs ? (clone $jobs)->where('active', true)->count() : 0;
        $applicantsCount = $profile?->applications()->count() ?? 0;
        $submissionsCount = $profile?->submissions()->count() ?? 0;
        $enquiriesCount = $user->employerEnquiries()->count();
        $eligibility = $this->employerEligibility->for($user);

        $companyName = $profile?->accountWorkspaceCompanyName() ?? '';

        return [
            'company_name' => $companyName !== '' ? $companyName : 'Company workspace',
            'business_id' => $profile?->business_id,
            'user_business_id' => $user->business_id,
            'ournear_id' => app(\App\Support\OurNearHireIdentity::class)->display($user, 'REC'),
            'username' => $user->username,
            'logo_url' => $profile?->logoUrl(),
            'photo_url' => $profile?->logoUrl(),
            'industry' => $profile?->displayIndustry() ?: null,
            'jobs_count' => $jobsCount,
            'active_jobs_count' => $activeJobsCount,
            'applicants_count' => $applicantsCount,
            'submissions_count' => $submissionsCount,
            'enquiries_count' => $enquiriesCount,
            'email_verified' => $user->emailVerified(),
            'mobile_verified' => $user->mobileVerified(),
            'ournear_verified' => $profile?->ournearVerified() === true,
            'posting_restricted' => $profile?->postingRestricted() === true,
            'eligibility' => $eligibility,
            'recent_applicants' => $this->recentOwnedApplicants($profile),
            'recent_submissions' => $this->recentOwnedSubmissions($profile),
            'next_actions' => $this->employerNextActions(
                $eligibility,
                $jobsCount,
                $applicantsCount,
                $submissionsCount,
                $enquiriesCount,
                $profile?->postingRestricted() === true,
            ),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function recentOwnedApplicants(?EmployerProfile $profile): array
    {
        if (! $profile) {
            return [];
        }

        return $profile->applications()
            ->with(['user.candidateProfile', 'job'])
            ->orderByDesc('applications.id')
            ->limit(5)
            ->get()
            ->map(fn (Application $application) => EmployerVisibleCandidate::fromApplication($application))
            ->values()
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function recentOwnedSubmissions(?EmployerProfile $profile): array
    {
        if (! $profile) {
            return [];
        }

        return $profile->submissions()
            ->with(['application.user.candidateProfile', 'job'])
            ->latest('submitted_at')
            ->latest('id')
            ->limit(5)
            ->get()
            ->map(fn (EmployerSubmission $submission) => EmployerVisibleCandidate::fromSubmission($submission))
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $eligibility
     * @return list<array{label: string, href: string, kind: string}>
     */
    private function employerNextActions(
        array $eligibility,
        int $jobsCount,
        int $applicantsCount,
        int $submissionsCount,
        int $enquiriesCount,
        bool $postingRestricted,
    ): array {
        $actions = [];

        if ($postingRestricted) {
            $actions[] = [
                'label' => 'Job posting is restricted. Complete your company profile or contact OurNear if this needs review.',
                'href' => '/account/company',
                'kind' => 'pending',
            ];
        } elseif (! ($eligibility['eligible'] ?? false)) {
            $actions[] = [
                'label' => $eligibility['next_action']
                    ?? $eligibility['reason']
                    ?? 'Complete posting eligibility before you can publish jobs.',
                'href' => '/account/company',
                'kind' => 'pending',
            ];
        } elseif ($jobsCount === 0) {
            $actions[] = [
                'label' => 'Create your first job to start receiving applicants.',
                'href' => '/account/jobs/create',
                'kind' => 'pending',
            ];
        }

        if ($applicantsCount > 0) {
            $actions[] = [
                'label' => $applicantsCount === 1
                    ? 'Review the latest applicant on your jobs.'
                    : 'Review '.$applicantsCount.' applicants on your jobs.',
                'href' => '/account/hiring/applications',
                'kind' => 'pending',
            ];
        } elseif ($jobsCount > 0) {
            $actions[] = [
                'label' => 'No applicants yet. Share your live jobs so candidates can apply.',
                'href' => '/account/jobs',
                'kind' => 'info',
            ];
        }

        if ($enquiriesCount > 0) {
            $actions[] = [
                'label' => $enquiriesCount === 1
                    ? 'View your workforce enquiry.'
                    : 'View '.$enquiriesCount.' workforce enquiries.',
                'href' => '/account/enquiries',
                'kind' => 'info',
            ];
        }

        return $actions;
    }

    /**
     * @return array<string, mixed>
     */
    private function candidateDashboard(User $user): array
    {
        $profile = $user->candidateProfile;
        $resumeCount = 0;
        $primary = null;
        $documentCount = 0;
        $categories = [];
        $verifiedCount = 0;

        if ($profile) {
            $profile->loadMissing(['industry', 'classifiedExperienceRange']);
            $resumeCount = $profile->resumes()->count();
            $primaryResume = $profile->resumes()
                ->where('is_primary', true)
                ->first();
            $primary = $primaryResume instanceof CandidateResume
                ? $this->primaryResumeSummary($primaryResume)
                : null;

            $documentRows = CandidateDocument::query()
                ->where('candidate_profile_id', $profile->id)
                ->selectRaw('document_type, verification_status, COUNT(*) as aggregate')
                ->groupBy('document_type', 'verification_status')
                ->get();

            $documentCount = (int) $documentRows->sum('aggregate');
            $verifiedCount = (int) $documentRows
                ->filter(fn (CandidateDocument $row) => $this->isVerified($row->verification_status))
                ->sum('aggregate');
            $categories = $documentRows
                ->groupBy(fn (CandidateDocument $row) => $this->documentTypeValue($row->document_type))
                ->map(function ($group) {
                    return [
                        'label' => $this->documentTypeLabel($group->first()?->document_type),
                        'count' => (int) $group->sum('aggregate'),
                    ];
                })
                ->values()
                ->all();
        }

        $savedJobs = SavedJob::query()
            ->where('user_id', $user->id)
            ->with('job')
            ->latest('created_at');

        $applications = $user->applications()
            ->with(['job.employer'])
            ->latest();

        $hasPrimaryResume = $primary !== null;

        return [
            'header' => [
                'name' => $user->name,
                'username' => $user->username,
                'business_id' => $user->business_id,
                'ournear_id' => app(\App\Support\OurNearHireIdentity::class)->display($user, 'CAN'),
                'photo_url' => $user->photoUrl(),
                'professional_headline' => $user->professional_headline,
                'designation' => $profile?->current_designation,
                'location' => $user->location,
                'industry' => $profile?->industry?->name,
                'experience' => $profile?->classifiedExperienceRange?->name ?: $profile?->experience,
                'profile_completion' => $user->calculateProfileCompletion(),
                'email_verified' => $user->emailVerified(),
            ],
            'next_action' => CandidateProfileGuidance::resolveNextAction($user, $hasPrimaryResume),
            'resume' => [
                'count' => $resumeCount,
                'primary' => $primary,
            ],
            'documents' => [
                'count' => $documentCount,
                'categories' => $categories,
                'verified_count' => $verifiedCount,
            ],
            'saved_jobs' => [
                'count' => (clone $savedJobs)->count(),
                'recent' => (clone $savedJobs)
                    ->limit(3)
                    ->get()
                    ->map(fn (SavedJob $saved) => $saved->toAccountArray())
                    ->values()
                    ->all(),
            ],
            'applications' => [
                'count' => (clone $applications)->count(),
                'status_summary' => $this->applicationStatusSummary($user),
                'recent' => (clone $applications)
                    ->limit(5)
                    ->get()
                    ->map(fn (Application $application) => $this->applicationSummary($application))
                    ->values()
                    ->all(),
            ],
            'recommended_jobs' => $this->candidateRecommendedJobs($user),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function candidateRecommendedJobs(User $user): array
    {
        $jobs = $this->jobListings->recommendedForCandidate($user, 6);
        $states = app(PublicJobStateService::class);

        return collect($jobs)
            ->map(fn (Job $job) => $states->decorate($job, $user))
            ->values()
            ->all();
    }

    /**
     * @return list<array{label: string, count: int}>
     */
    private function applicationStatusSummary(User $user): array
    {
        $totals = [
            ApplicationStatus::New->toCandidateStatusLabel() => 0,
            ApplicationStatus::Reviewing->toCandidateStatusLabel() => 0,
            ApplicationStatus::Shortlisted->toCandidateStatusLabel() => 0,
            ApplicationStatus::Interview->toCandidateStatusLabel() => 0,
        ];

        $rows = $user->applications()
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->get();

        foreach ($rows as $row) {
            $rawStatus = $row->status;
            $status = $rawStatus instanceof ApplicationStatus
                ? $rawStatus
                : ApplicationStatus::tryFrom((string) $rawStatus);
            if (! $status instanceof ApplicationStatus) {
                continue;
            }

            $label = $status->toCandidateStatusLabel();
            if (! array_key_exists($label, $totals)) {
                continue;
            }

            $totals[$label] += (int) $row->aggregate;
        }

        return collect($totals)
            ->map(fn (int $count, string $label) => ['label' => $label, 'count' => $count])
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function primaryResumeSummary(CandidateResume $resume): array
    {
        return [
            'title' => $resume->title,
            'status' => $resume->status?->value,
            'status_label' => $resume->status?->label(),
            'is_primary' => true,
            'updated_at' => $resume->updated_at?->toDateTimeString(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function applicationSummary(Application $application): array
    {
        $job = $application->job;

        return [
            'title' => $job?->title ?? 'Role',
            'slug' => $job?->slug,
            'company' => $job?->employer?->publicDisplayCompany() ?: $job?->project,
            'status' => $application->status->toCandidateLabel(),
            'status_detail' => $application->status->toCandidateStatusLabel(),
            'applied_at' => $application->created_at?->toDateTimeString(),
            'href' => route('account.applications.show', $application, false),
        ];
    }

    private function isVerified(mixed $status): bool
    {
        if ($status instanceof DocumentVerificationStatus) {
            return $status === DocumentVerificationStatus::Verified;
        }

        return (string) $status === DocumentVerificationStatus::Verified->value;
    }

    private function documentTypeValue(mixed $type): string
    {
        if ($type instanceof DocumentType) {
            return $type->value;
        }

        return (string) $type;
    }

    private function documentTypeLabel(mixed $type): string
    {
        if ($type instanceof DocumentType) {
            return $type->label();
        }

        return DocumentType::tryFrom((string) $type)?->label() ?? 'Other';
    }

    /**
     * @param  array<string, mixed>  $profile
     * @return array<string, mixed>
     */
    private function enrichCandidateHomeProfile(User $user, array $profile): array
    {
        $user->loadMissing([
            'candidateProfile.skills',
            'candidateProfile.employmentHistories',
            'candidateProfile.educations',
            'candidateProfile.projects',
            'candidateProfile.certifications',
            'candidateProfile.languages',
            'candidateProfile.preferredLocations',
            'candidateProfile.preferredEmploymentTypes',
            'candidateProfile.industry',
            'candidateProfile.jobCategory',
        ]);

        $candidateProfile = $user->candidateProfile;
        $profile['experience'] = $candidateProfile?->experience;
        $profile['qualification'] = $candidateProfile?->qualification;
        $profile['professional'] = $candidateProfile?->toProfessionalArray();

        $libraryResume = $candidateProfile?->resumes()
            ->where('status', FileLifecycleStatus::Active)
            ->orderByDesc('is_primary')
            ->orderByDesc('id')
            ->first();
        $profile['library_resume'] = [
            'has_upload' => $libraryResume instanceof CandidateResume,
            'title' => $libraryResume instanceof CandidateResume ? $libraryResume->title : null,
        ];

        return $profile;
    }
}
