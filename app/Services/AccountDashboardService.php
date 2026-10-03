<?php

namespace App\Services;

use App\Enums\ApplicationStatus;
use App\Enums\DocumentType;
use App\Enums\DocumentVerificationStatus;
use App\Models\Application;
use App\Models\CandidateDocument;
use App\Models\CandidateResume;
use App\Models\EmployerProfile;
use App\Models\EmployerSubmission;
use App\Models\Job;
use App\Models\SavedJob;
use App\Models\User;
use App\Support\EmployerVisibleCandidate;

class AccountDashboardService
{
    public function __construct(private EmployerPostingEligibilityService $employerEligibility) {}

    /**
     * @return array{role: string, profile: array<string, mixed>, dashboard: ?array<string, mixed>}
     */
    public function home(User $user): array
    {
        $user->loadMissing(['candidateProfile', 'employerProfile']);

        $profile = $user->toAccountProfileArray();
        if ($user->isEmployer()) {
            $profile = array_merge($profile, $user->employerProfile?->toAccountArray() ?? []);
        }

        return [
            'role' => $user->role->value,
            'profile' => $profile,
            'dashboard' => $user->isCandidate()
                ? $this->candidateDashboard($user)
                : ($user->isEmployer() ? $this->employerDashboard($user) : null),
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
        if ($profile instanceof EmployerProfile) {
            $activeJobs = $profile->jobs()
                ->where('active', true)
                ->withCount('applications')
                ->orderByDesc('updated_at')
                ->limit(5)
                ->get()
                ->map(fn (Job $job) => [
                    'title' => $job->title,
                    'location' => $job->displayLocation(),
                    'applications_count' => (int) $job->applications_count,
                    'status_label' => $job->statusLabel(),
                    'href' => route('account.employer.jobs.applicants', $job, false),
                ])
                ->values()
                ->all();
        }

        $dashboard['user_name'] = $user->name;
        $dashboard['actions_required'] = array_values(array_filter(
            $dashboard['next_actions'],
            fn (array $action) => ($action['kind'] ?? '') === 'pending',
        ));
        $dashboard['active_jobs'] = $activeJobs;
        $dashboard['can_post_job'] = ($dashboard['eligibility']['eligible'] ?? false)
            && ! ($dashboard['posting_restricted'] ?? false);

        return $dashboard;
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

        return [
            'company_name' => $profile?->displayCompany() ?? 'Company profile',
            'business_id' => $profile?->business_id,
            'user_business_id' => $user->business_id,
            'ournear_id' => app(\App\Support\OurNearHireIdentity::class)->display($user, 'REC'),
            'username' => $user->username,
            'photo_url' => $user->photoUrl(),
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
                'href' => '/account/profile',
                'kind' => 'pending',
            ];
        } elseif (! ($eligibility['eligible'] ?? false)) {
            $actions[] = [
                'label' => $eligibility['next_action']
                    ?? $eligibility['reason']
                    ?? 'Complete posting eligibility before you can publish jobs.',
                'href' => '/account/profile',
                'kind' => 'pending',
            ];
        } elseif ($jobsCount === 0) {
            $actions[] = [
                'label' => 'Post your first job to start receiving applicants.',
                'href' => '/account/jobs/create',
                'kind' => 'pending',
            ];
        }

        if ($applicantsCount > 0) {
            $actions[] = [
                'label' => $applicantsCount === 1
                    ? 'Review the latest applicant on your jobs.'
                    : 'Review '.$applicantsCount.' applicants on your jobs.',
                'href' => '/account/jobs',
                'kind' => 'pending',
            ];
        } elseif ($jobsCount > 0) {
            $actions[] = [
                'label' => 'No applicants yet. Share your live jobs so candidates can apply.',
                'href' => '/account/jobs',
                'kind' => 'info',
            ];
        }

        if ($submissionsCount > 0) {
            $actions[] = [
                'label' => $submissionsCount === 1
                    ? 'Review the candidate OurNear submitted to your company.'
                    : 'Review '.$submissionsCount.' candidates submitted to your company.',
                'href' => '/account/submissions',
                'kind' => 'pending',
            ];
        } else {
            $actions[] = [
                'label' => 'Submitted candidates will appear here when OurNear shares them with you.',
                'href' => '/account/submissions',
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

        return [
            'header' => [
                'name' => $user->name,
                'username' => $user->username,
                'business_id' => $user->business_id,
                'ournear_id' => app(\App\Support\OurNearHireIdentity::class)->display($user, 'CAN'),
                'photo_url' => $user->photoUrl(),
                'designation' => $profile?->current_designation,
                'location' => $user->location,
                'profile_completion' => (int) $user->profile_completion,
                'email_verified' => $user->emailVerified(),
            ],
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
        ];
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
}
