<?php

namespace App\Services\Admin;

use App\Enums\ApplicationStatus;
use App\Enums\EmployerEnquiryStatus;
use App\Enums\JobPublishingStatus;
use App\Models\Application;
use App\Models\EmployerEnquiry;
use App\Models\Job;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DashboardService
{
    /**
     * @return array{
     *     jobs: int|null,
     *     active_jobs: int|null,
     *     applications: int|null,
     *     employer_enquiries: int|null,
     *     contact_enquiries: int|null
     * }
     */
    public function stats(): array
    {
        return [
            'jobs' => $this->recruitmentJobsCount(),
            'active_jobs' => $this->activeRecruitmentJobsCount(),
            'applications' => $this->countIfExists('applications'),
            'employer_enquiries' => $this->countIfExists('employer_enquiries'),
            'contact_enquiries' => $this->countIfExists('contact_enquiries'),
            'pipeline' => $this->pipeline(),
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

    /**
     * @param  list<string>  $columns
     * @return list<array<string, mixed>>
     */
    private function recentRows(string $table, array $columns): array
    {
        if (! Schema::hasTable($table)) {
            return [];
        }

        $available = array_values(array_filter(
            $columns,
            fn (string $column) => Schema::hasColumn($table, $column)
        ));

        if ($available === []) {
            return [];
        }

        return DB::table($table)
            ->select($available)
            ->orderByDesc(in_array('created_at', $available, true) ? 'created_at' : $available[0])
            ->limit(5)
            ->get()
            ->map(fn ($row) => (array) $row)
            ->all();
    }
}
