<?php

namespace App\Services\Admin;

use App\Enums\AccountStatus;
use App\Enums\ApplicationStatus;
use App\Enums\EmployerEnquiryStatus;
use App\Enums\EmployerSubmissionStatus;
use App\Enums\InterviewOutcome;
use App\Enums\InvoiceStatus;
use App\Enums\JobPublishingStatus;
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
    public function stats(): array
    {
        $pipeline = $this->pipeline();

        return [
            'jobs' => $this->recruitmentJobsCount(),
            'active_jobs' => $this->activeRecruitmentJobsCount(),
            'applications' => $this->countIfExists('applications'),
            'employer_enquiries' => $this->countIfExists('employer_enquiries'),
            'contact_enquiries' => $this->countIfExists('contact_enquiries'),
            'pipeline' => $pipeline,
            'platform' => $this->platform(),
            'recruitment' => $this->recruitment(),
            'recruitment_pipeline' => $this->recruitmentPipeline(),
            'pending_actions' => $this->pendingActions(),
        ];
    }

    /**
     * @return array<string, int|null>
     */
    public function pipeline(): array
    {
        if (! Schema::hasTable('applications')) {
            return [
                'new' => null,
                'reviewing' => null,
                'shortlisted' => null,
                'interview' => null,
                'selected' => null,
                'placed' => null,
                'published_open_jobs' => null,
                'employer_enquiries_new' => null,
                'employer_enquiries_contacted' => null,
                'employer_enquiries_in_progress' => null,
                'jobs_closing_soon' => null,
            ];
        }

        $applicationCounts = Application::query()
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $enquiryCounts = Schema::hasTable('employer_enquiries')
            ? EmployerEnquiry::query()
                ->selectRaw('status, COUNT(*) as aggregate')
                ->groupBy('status')
                ->pluck('aggregate', 'status')
            : collect();

        return [
            'new' => (int) ($applicationCounts[ApplicationStatus::New->value] ?? 0),
            'reviewing' => (int) ($applicationCounts[ApplicationStatus::Reviewing->value] ?? 0),
            'shortlisted' => (int) ($applicationCounts[ApplicationStatus::Shortlisted->value] ?? 0),
            'interview' => (int) ($applicationCounts[ApplicationStatus::Interview->value] ?? 0),
            'selected' => (int) ($applicationCounts[ApplicationStatus::Selected->value] ?? 0),
            'placed' => (int) ($applicationCounts[ApplicationStatus::Placed->value] ?? 0),
            'published_open_jobs' => Job::query()
                ->where('active', true)
                ->where('publishing_status', JobPublishingStatus::Published)
                ->count(),
            'employer_enquiries_new' => (int) ($enquiryCounts[EmployerEnquiryStatus::New->value] ?? 0),
            'employer_enquiries_contacted' => (int) ($enquiryCounts[EmployerEnquiryStatus::Contacted->value] ?? 0),
            'employer_enquiries_in_progress' => (int) ($enquiryCounts[EmployerEnquiryStatus::InProgress->value] ?? 0),
            'jobs_closing_soon' => Job::query()
                ->where('active', true)
                ->where('publishing_status', JobPublishingStatus::Published)
                ->whereNotNull('closing_date')
                ->whereDate('closing_date', '>=', now()->toDateString())
                ->whereDate('closing_date', '<=', now()->addDays(7)->toDateString())
                ->count(),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function recentApplications(): array
    {
        if (! Schema::hasTable('applications')) {
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
    public function recentEmployerEnquiries(): array
    {
        if (! Schema::hasTable('employer_enquiries')) {
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
    private function platform(): array
    {
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
    private function recruitment(): array
    {
        $selected = Schema::hasTable('applications')
            ? Application::query()->where('status', ApplicationStatus::Selected->value)->count()
            : null;

        return [
            'active_jobs' => $this->metric($this->activeRecruitmentJobsCount(), '/admin/jobs?active=1'),
            'applications' => $this->metric($this->countIfExists('applications'), '/admin/applications'),
            'candidates' => $this->metric($this->candidatesCount(), '/admin/candidates'),
            'screening_pending' => $this->metric($this->screeningPendingCount(), '/admin/screening?screening_decision=pending'),
            'shortlisted' => $this->metric($this->shortlistedCount(), '/admin/shortlisted'),
            'employer_submissions' => $this->metric($this->countIfExists('employer_submissions'), '/admin/submissions'),
            'selected' => $this->metric($selected, '/admin/applications?status='.urlencode(ApplicationStatus::Selected->value)),
            'placements' => $this->metric($this->countIfExists('placements'), '/admin/placements'),
            'joined_employees' => $this->metric($this->countIfExists('employees'), '/admin/employees'),
            'replacement_cases' => $this->metric($this->countIfExists('replacements'), '/admin/replacements'),
        ];
    }

    /**
     * @return list<array{key: string, label: string, value: int|null, href: string|null}>
     */
    private function recruitmentPipeline(): array
    {
        $recruitment = $this->recruitment();

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
    private function pendingActions(): array
    {
        $items = [
            [
                'key' => 'screening_pending',
                'label' => 'Screening pending',
                'value' => $this->screeningPendingCount(),
                'href' => '/admin/screening?screening_decision=pending',
            ],
            [
                'key' => 'employer_responses',
                'label' => 'Employer responses',
                'value' => $this->employerResponsesPendingCount(),
                'href' => '/admin/submissions',
            ],
            [
                'key' => 'interviews',
                'label' => 'Interviews',
                'value' => $this->pendingInterviewsCount(),
                'href' => '/admin/applications?status='.urlencode(ApplicationStatus::Interview->value),
            ],
            [
                'key' => 'payments',
                'label' => 'Payments',
                'value' => $this->openPaymentsCount(),
                'href' => '/admin/finance',
            ],
            [
                'key' => 'replacement_cases',
                'label' => 'Replacement cases',
                'value' => $this->openReplacementsCount(),
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
}
