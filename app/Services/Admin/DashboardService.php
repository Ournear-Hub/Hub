<?php

namespace App\Services\Admin;

use App\Enums\AccountStatus;
use App\Enums\ApplicationStatus;
use App\Enums\EmployerEnquiryStatus;
use App\Enums\EmployerSubmissionStatus;
use App\Enums\InterviewOutcome;
use App\Enums\InvoiceStatus;
use App\Enums\JobPublishingStatus;
use App\Enums\PermissionName;
use App\Enums\ReplacementStatus;
use App\Enums\ScreeningDecision;
use App\Enums\UserRole;
use App\Models\Application;
use App\Models\CandidateProfile;
use App\Models\EmployerEnquiry;
use App\Models\Job;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DashboardService
{
    /**
     * @return array<string, mixed>
     */
    public function stats(?User $user = null): array
    {
        $canJobs = $this->canSee($user, PermissionName::JobsView);
        $canApplications = $this->canSee($user, PermissionName::ApplicationsView);
        $canEmployers = $this->canSee($user, PermissionName::EmployersView);

        return [
            'jobs' => $canJobs ? $this->recruitmentJobsCount() : null,
            'active_jobs' => $canJobs ? $this->activeRecruitmentJobsCount() : null,
            'applications' => $canApplications ? $this->countIfExists('applications') : null,
            'employer_enquiries' => $canEmployers ? $this->countIfExists('employer_enquiries') : null,
            'contact_enquiries' => $canEmployers ? $this->countIfExists('contact_enquiries') : null,
            'pipeline' => $this->pipeline($user),
            'platform' => $this->platform($user),
            'recruitment' => $this->recruitment($user),
            'recruitment_pipeline' => $this->recruitmentPipeline($user),
            'pending_actions' => $this->pendingActions($user),
        ];
    }

    /**
     * @return array<string, int|null>
     */
    public function pipeline(?User $user = null): array
    {
        $canApplications = Schema::hasTable('applications') && $this->canSee($user, PermissionName::ApplicationsView);
        $applicationCounts = $canApplications
            ? Application::query()
                ->selectRaw('status, COUNT(*) as aggregate')
                ->groupBy('status')
                ->pluck('aggregate', 'status')
            : collect();

        $enquiryCounts = Schema::hasTable('employer_enquiries') && $this->canSee($user, PermissionName::EmployersView)
            ? EmployerEnquiry::query()
                ->selectRaw('status, COUNT(*) as aggregate')
                ->groupBy('status')
                ->pluck('aggregate', 'status')
            : collect();

        return [
            'new' => $canApplications ? (int) ($applicationCounts[ApplicationStatus::New->value] ?? 0) : null,
            'reviewing' => $canApplications ? (int) ($applicationCounts[ApplicationStatus::Reviewing->value] ?? 0) : null,
            'shortlisted' => $canApplications ? (int) ($applicationCounts[ApplicationStatus::Shortlisted->value] ?? 0) : null,
            'interview' => $canApplications ? (int) ($applicationCounts[ApplicationStatus::Interview->value] ?? 0) : null,
            'selected' => $canApplications ? (int) ($applicationCounts[ApplicationStatus::Selected->value] ?? 0) : null,
            'placed' => $canApplications ? (int) ($applicationCounts[ApplicationStatus::Placed->value] ?? 0) : null,
            'published_open_jobs' => $this->canSee($user, PermissionName::JobsView)
                ? Job::query()
                    ->where('active', true)
                    ->where('publishing_status', JobPublishingStatus::Published)
                    ->count()
                : null,
            'employer_enquiries_new' => $this->canSee($user, PermissionName::EmployersView)
                ? (int) ($enquiryCounts[EmployerEnquiryStatus::New->value] ?? 0)
                : null,
            'employer_enquiries_contacted' => $this->canSee($user, PermissionName::EmployersView)
                ? (int) ($enquiryCounts[EmployerEnquiryStatus::Contacted->value] ?? 0)
                : null,
            'employer_enquiries_in_progress' => $this->canSee($user, PermissionName::EmployersView)
                ? (int) ($enquiryCounts[EmployerEnquiryStatus::InProgress->value] ?? 0)
                : null,
            'jobs_closing_soon' => $this->canSee($user, PermissionName::JobsView)
                ? Job::query()
                    ->where('active', true)
                    ->where('publishing_status', JobPublishingStatus::Published)
                    ->whereNotNull('closing_date')
                    ->whereDate('closing_date', '>=', now()->toDateString())
                    ->whereDate('closing_date', '<=', now()->addDays(7)->toDateString())
                    ->count()
                : null,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function recentApplications(?User $user = null): array
    {
        if (! Schema::hasTable('applications') || ! $this->canSee($user, PermissionName::ApplicationsView)) {
            return [];
        }

        return Application::query()
            ->with('job:id,title,slug')
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn (Application $application) => [
                'id' => $application->id,
                'full_name' => $application->full_name,
                'job_title' => $application->job?->title,
                'created_at' => $application->created_at?->toDateTimeString(),
                'href' => '/admin/applications?application='.$application->id,
            ])
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function recentEmployerEnquiries(?User $user = null): array
    {
        if (! Schema::hasTable('employer_enquiries') || ! $this->canSee($user, PermissionName::EmployersView)) {
            return [];
        }

        return EmployerEnquiry::query()
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn (EmployerEnquiry $enquiry) => [
                'id' => $enquiry->id,
                'company_name' => $enquiry->company_name,
                'contact_person' => $enquiry->contact_person,
                'status' => $enquiry->status->value,
                'created_at' => $enquiry->created_at?->toDateTimeString(),
                'href' => '/admin/employer-enquiries?enquiry='.$enquiry->id,
            ])
            ->all();
    }

    /**
     * @return array<string, array{value: int|null, href: string|null}>
     */
    private function platform(?User $user = null): array
    {
        if (! $this->canSee($user, PermissionName::UsersView)) {
            return [
                'total_users' => $this->metric(null, null),
                'new_users' => $this->metric(null, null),
                'active_users' => $this->metric(null, null),
                'verified_users' => $this->metric(null, null),
                'blocked_users' => $this->metric(null, null),
            ];
        }

        if (! Schema::hasTable('users')) {
            return [
                'total_users' => $this->metric(null, '/admin/users'),
                'new_users' => $this->metric(null, '/admin/users?segment=new'),
                'active_users' => $this->metric(null, '/admin/users?segment=active'),
                'verified_users' => $this->metric(null, '/admin/users?segment=verified'),
                'blocked_users' => $this->metric(null, '/admin/users?segment=blocked'),
            ];
        }

        $blocked = User::query()
            ->whereIn('account_status', [
                AccountStatus::Blocked->value,
                AccountStatus::Suspended->value,
            ])
            ->count();

        return [
            'total_users' => $this->metric(User::query()->count(), '/admin/users'),
            'new_users' => $this->metric(
                User::query()->where('created_at', '>=', now()->subDays(30))->count(),
                '/admin/users?segment=new',
            ),
            'active_users' => $this->metric($this->activeUsersCount(), '/admin/users?segment=active'),
            'verified_users' => $this->metric(
                User::query()->whereNotNull('email_verified_at')->count(),
                '/admin/users?segment=verified',
            ),
            'blocked_users' => $this->metric($blocked, '/admin/users?segment=blocked'),
        ];
    }

    /**
     * @return array<string, array{value: int|null, href: string|null}>
     */
    private function recruitment(?User $user = null): array
    {
        $selected = Schema::hasTable('applications') && $this->canSee($user, PermissionName::ApplicationsView)
            ? Application::query()->where('status', ApplicationStatus::Selected->value)->count()
            : null;

        return [
            'active_jobs' => $this->metric(
                $this->canSee($user, PermissionName::JobsView) ? $this->activeRecruitmentJobsCount() : null,
                $this->canSee($user, PermissionName::JobsView) ? '/admin/jobs?active=1' : null,
            ),
            'applications' => $this->metric(
                $this->canSee($user, PermissionName::ApplicationsView) ? $this->countIfExists('applications') : null,
                $this->canSee($user, PermissionName::ApplicationsView) ? '/admin/applications' : null,
            ),
            'candidates' => $this->metric(
                $this->canSee($user, PermissionName::CandidatesView) ? $this->candidatesCount() : null,
                $this->canSee($user, PermissionName::CandidatesView) ? '/admin/candidates' : null,
            ),
            'screening_pending' => $this->metric(
                $this->canSee($user, PermissionName::ApplicationsView) ? $this->screeningPendingCount() : null,
                $this->canSee($user, PermissionName::ApplicationsView) ? '/admin/screening?screening_decision=pending' : null,
            ),
            'shortlisted' => $this->metric(
                $this->canSee($user, PermissionName::ApplicationsView) ? $this->shortlistedCount() : null,
                $this->canSee($user, PermissionName::ApplicationsView) ? '/admin/shortlisted' : null,
            ),
            'employer_submissions' => $this->metric(
                $this->canSee($user, PermissionName::SubmissionsView) ? $this->countIfExists('employer_submissions') : null,
                $this->canSee($user, PermissionName::SubmissionsView) ? '/admin/submissions' : null,
            ),
            'selected' => $this->metric(
                $selected,
                $this->canSee($user, PermissionName::ApplicationsView)
                    ? '/admin/applications?status='.urlencode(ApplicationStatus::Selected->value)
                    : null,
            ),
            'placements' => $this->metric(
                $this->canSee($user, PermissionName::PlacementsView) ? $this->countIfExists('placements') : null,
                $this->canSee($user, PermissionName::PlacementsView) ? '/admin/placements' : null,
            ),
            'joined_employees' => $this->metric(
                $this->canSee($user, PermissionName::EmployeesView) ? $this->countIfExists('employees') : null,
                $this->canSee($user, PermissionName::EmployeesView) ? '/admin/employees' : null,
            ),
            'replacement_cases' => $this->metric(
                $this->canSee($user, PermissionName::ReplacementsView) ? $this->countIfExists('replacements') : null,
                $this->canSee($user, PermissionName::ReplacementsView) ? '/admin/replacements' : null,
            ),
        ];
    }

    /**
     * @return list<array{key: string, label: string, value: int|null, href: string|null}>
     */
    private function recruitmentPipeline(?User $user = null): array
    {
        $recruitment = $this->recruitment($user);

        return [
            $this->stage('applications', 'Applications', $recruitment['applications']),
            $this->stage('screening', 'Screening', $recruitment['screening_pending']),
            $this->stage('shortlisted', 'Shortlisted', $recruitment['shortlisted']),
            $this->stage('employer_submission', 'Employer Submission', $recruitment['employer_submissions']),
            $this->stage('selected', 'Selected', $recruitment['selected']),
            $this->stage('placement', 'Placement', $recruitment['placements']),
            $this->stage('joined', 'Joined', $recruitment['joined_employees']),
        ];
    }

    /**
     * @return list<array{key: string, label: string, value: int|null, href: string|null}>
     */
    private function pendingActions(?User $user = null): array
    {
        $items = [
            [
                'key' => 'screening_pending',
                'label' => 'Screening pending',
                'value' => $this->canSee($user, PermissionName::ApplicationsView) ? $this->screeningPendingCount() : null,
                'href' => '/admin/screening?screening_decision=pending',
            ],
            [
                'key' => 'employer_responses',
                'label' => 'Employer responses',
                'value' => $this->canSee($user, PermissionName::SubmissionsView) ? $this->employerResponsesPendingCount() : null,
                'href' => '/admin/submissions',
            ],
            [
                'key' => 'interviews',
                'label' => 'Interviews',
                'value' => $this->canSee($user, PermissionName::ApplicationsView) ? $this->pendingInterviewsCount() : null,
                'href' => '/admin/applications?status='.urlencode(ApplicationStatus::Interview->value),
            ],
            [
                'key' => 'payments',
                'label' => 'Payments',
                'value' => $this->canSee($user, PermissionName::FinanceView) ? $this->openPaymentsCount() : null,
                'href' => '/admin/finance',
            ],
            [
                'key' => 'replacement_cases',
                'label' => 'Replacement cases',
                'value' => $this->canSee($user, PermissionName::ReplacementsView) ? $this->openReplacementsCount() : null,
                'href' => '/admin/replacements',
            ],
        ];

        return array_values(array_filter(
            $items,
            fn (array $item) => $item['value'] !== null
        ));
    }

    /**
     * @param  array{value: int|null, href: string|null}  $metric
     * @return array{key: string, label: string, value: int|null, href: string|null}
     */
    private function stage(string $key, string $label, array $metric): array
    {
        return [
            'key' => $key,
            'label' => $label,
            'value' => $metric['value'],
            'href' => $metric['href'],
        ];
    }

    /**
     * @return array{value: int|null, href: string|null}
     */
    private function metric(?int $value, ?string $href): array
    {
        return [
            'value' => $value,
            'href' => $href,
        ];
    }

    private function activeUsersCount(): int
    {
        return User::query()
            ->where(function ($query) {
                $query->where('account_status', AccountStatus::Active->value)
                    ->orWhere(function ($legacy) {
                        $legacy->where('is_active', true)
                            ->where(function ($empty) {
                                $empty->whereNull('account_status')->orWhere('account_status', '');
                            });
                    });
            })
            ->count();
    }

    private function candidatesCount(): ?int
    {
        if (! Schema::hasTable('candidate_profiles')) {
            return null;
        }

        return CandidateProfile::query()
            ->whereHas('user', fn ($user) => $user->where('role', UserRole::Candidate->value))
            ->count();
    }

    private function screeningPendingCount(): ?int
    {
        if (! Schema::hasTable('applications')) {
            return null;
        }

        return Application::query()
            ->where(function ($query) {
                $query->whereDoesntHave('screening')
                    ->orWhereHas('screening', fn ($screening) => $screening->where('decision', ScreeningDecision::Pending->value));
            })
            ->count();
    }

    private function shortlistedCount(): ?int
    {
        if (! Schema::hasTable('applications')) {
            return null;
        }

        return Application::query()
            ->where(function ($query) {
                $query->where('status', ApplicationStatus::Shortlisted->value)
                    ->orWhereHas('screening', fn ($screening) => $screening->where('decision', ScreeningDecision::Shortlisted->value));
            })
            ->count();
    }

    private function employerResponsesPendingCount(): ?int
    {
        if (! Schema::hasTable('employer_submissions')) {
            return null;
        }

        return (int) DB::table('employer_submissions')
            ->whereIn('status', [
                EmployerSubmissionStatus::Submitted->value,
                EmployerSubmissionStatus::UnderReview->value,
            ])
            ->count();
    }

    private function pendingInterviewsCount(): ?int
    {
        if (! Schema::hasTable('application_interviews')) {
            return null;
        }

        return (int) DB::table('application_interviews')
            ->where('outcome', InterviewOutcome::Pending->value)
            ->count();
    }

    private function openPaymentsCount(): ?int
    {
        if (! Schema::hasTable('invoices')) {
            return null;
        }

        return (int) DB::table('invoices')
            ->whereIn('status', [
                InvoiceStatus::Issued->value,
                InvoiceStatus::PartiallyPaid->value,
            ])
            ->count();
    }

    private function openReplacementsCount(): ?int
    {
        if (! Schema::hasTable('replacements')) {
            return null;
        }

        return (int) DB::table('replacements')
            ->whereIn('status', [
                ReplacementStatus::Requested->value,
                ReplacementStatus::InProgress->value,
                ReplacementStatus::Placed->value,
            ])
            ->count();
    }

    private function recruitmentJobsCount(): ?int
    {
        if (! Schema::hasTable('job_listings')) {
            return null;
        }

        return Job::query()->count();
    }

    private function activeRecruitmentJobsCount(): ?int
    {
        if (! Schema::hasTable('job_listings')) {
            return null;
        }

        return Job::query()->where('active', true)->count();
    }

    private function countIfExists(string $table): ?int
    {
        if (! Schema::hasTable($table)) {
            return null;
        }

        return (int) DB::table($table)->count();
    }

    private function canSee(?User $user, PermissionName $permission): bool
    {
        if ($user === null) {
            return true;
        }

        if ($user->hasPermission($permission->value)) {
            return true;
        }

        $manage = match ($permission) {
            PermissionName::JobsView => PermissionName::JobsManage,
            PermissionName::ApplicationsView => PermissionName::ApplicationsManage,
            PermissionName::EmployersView => PermissionName::EmployersManage,
            PermissionName::UsersView => PermissionName::UsersManage,
            PermissionName::CandidatesView => PermissionName::CandidatesManage,
            PermissionName::SubmissionsView => PermissionName::SubmissionsManage,
            PermissionName::PlacementsView => PermissionName::PlacementsManage,
            PermissionName::EmployeesView => PermissionName::EmployeesManage,
            PermissionName::ReplacementsView => PermissionName::ReplacementsManage,
            PermissionName::FinanceView => PermissionName::FinanceManage,
            default => null,
        };

        return $manage !== null && $user->hasPermission($manage->value);
    }
}
