<?php

namespace App\Services;

use App\Models\Application;
use App\Models\ContactEnquiry;
use App\Models\EmployerEnquiry;
use App\Models\Job;
use App\Models\SavedJob;
use App\Models\User;
class AccountContextualSearchService
{
    /**
     * @return array<string, mixed>
     */
    public function forUser(User $user, string $keyword, string $scope = 'auto'): array
    {
        $term = trim($keyword);
        if ($term === '' || mb_strlen($term) < 2) {
            return $this->emptyResult($user, $this->resolveScope($user, $scope));
        }

        $like = '%'.$term.'%';
        $resolvedScope = $this->resolveScope($user, $scope);

        if ($resolvedScope === 'employer') {
            return $this->employerWorkspace($user, $like);
        }

        return $this->candidateWorkspace($user, $like);
    }

    private function resolveScope(User $user, string $scope): string
    {
        if ($scope === 'employer' && $user->canAccessHiringWorkspace()) {
            return 'employer';
        }

        if ($scope === 'candidate' && $user->isCandidate()) {
            return 'candidate';
        }

        if ($scope === 'employer' || ($scope === 'auto' && $user->canAccessHiringWorkspace() && ! $user->isCandidate())) {
            return 'employer';
        }

        if ($user->isCandidate()) {
            return 'candidate';
        }

        return $user->canAccessHiringWorkspace() ? 'employer' : 'candidate';
    }

    /**
     * @return array<string, mixed>
     */
    private function candidateWorkspace(User $user, string $like): array
    {
        $applications = Application::query()
            ->where('user_id', $user->id)
            ->whereHas('job', fn ($job) => $job->where('title', 'like', $like))
            ->with('job.employer')
            ->latest('id')
            ->limit(8)
            ->get()
            ->map(fn (Application $application) => [
                'label' => $application->job?->title ?? 'Application',
                'meta' => $application->job?->employer?->publicDisplayCompany(),
                'url' => route('account.applications.show', $application),
            ])
            ->values()
            ->all();

        $savedJobs = SavedJob::query()
            ->where('user_id', $user->id)
            ->whereHas('job', fn ($job) => $job->where('title', 'like', $like))
            ->with('job.employer')
            ->latest('id')
            ->limit(8)
            ->get()
            ->map(fn (SavedJob $saved) => [
                'label' => $saved->job?->title ?? 'Saved job',
                'meta' => $saved->job?->employer?->publicDisplayCompany(),
                'url' => $saved->job ? route('jobs.show', $saved->job) : '/account/saved-jobs',
            ])
            ->values()
            ->all();

        return [
            'scope' => 'candidate',
            'applications' => $applications,
            'saved_jobs' => $savedJobs,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function employerWorkspace(User $user, string $like): array
    {
        $profile = $user->employerProfile;
        if ($profile === null) {
            return $this->emptyResult($user, 'employer');
        }

        $jobs = Job::query()
            ->where('employer_id', $profile->id)
            ->where('title', 'like', $like)
            ->latest('id')
            ->limit(8)
            ->get()
            ->map(fn (Job $job) => [
                'label' => $job->title,
                'meta' => 'Your job',
                'url' => route('employer.jobs.edit', $job),
            ])
            ->values()
            ->all();

        $staffing = EmployerEnquiry::query()
            ->where('user_id', $user->id)
            ->where(function ($query) use ($like) {
                $query->where('company_name', 'like', $like)
                    ->orWhere('workforce_type', 'like', $like)
                    ->orWhere('project_location', 'like', $like);
            })
            ->latest('id')
            ->limit(5)
            ->get()
            ->map(fn (EmployerEnquiry $enquiry) => [
                'label' => $enquiry->workforce_type.' · '.$enquiry->project_location,
                'meta' => 'Staffing request',
                'url' => route('account.enquiries.show', $enquiry),
            ])
            ->values()
            ->all();

        $recruitment = ContactEnquiry::query()
            ->where('interested_in', 'Recruitment')
            ->where(function ($query) use ($like, $user) {
                $query->where('message', 'like', $like)
                    ->orWhere('full_name', 'like', $like);
                if ($user->email) {
                    $query->orWhere('email', $user->email);
                }
            })
            ->latest('id')
            ->limit(5)
            ->get()
            ->map(fn (ContactEnquiry $enquiry) => [
                'label' => 'Recruitment requirement',
                'meta' => $enquiry->created_at?->toDateString(),
                'url' => route('account.recruitment-support.create'),
            ])
            ->values()
            ->all();

        $applications = Application::query()
            ->whereHas('job', fn ($job) => $job->where('employer_id', $profile->id))
            ->where(function ($query) use ($like) {
                $query->whereHas('user', fn ($candidate) => $candidate->where('name', 'like', $like))
                    ->orWhereHas('job', fn ($job) => $job->where('title', 'like', $like));
            })
            ->with(['job', 'user'])
            ->latest('id')
            ->limit(8)
            ->get()
            ->map(fn (Application $application) => [
                'label' => $application->user?->name ?? 'Applicant',
                'meta' => $application->job?->title,
                'url' => $application->job
                    ? route('employer.jobs.application', ['job' => $application->job, 'application' => $application])
                    : route('account.hiring.applications'),
            ])
            ->values()
            ->all();

        return [
            'scope' => 'employer',
            'jobs' => $jobs,
            'applicants' => $applications,
            'staffing_requests' => $staffing,
            'recruitment_requests' => $recruitment,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyResult(User $user, ?string $scope = null): array
    {
        $resolvedScope = $scope ?? ($user->isCandidate() ? 'candidate' : ($user->canAccessHiringWorkspace() ? 'employer' : 'account'));

        return match ($resolvedScope) {
            'employer' => ['scope' => 'employer', 'jobs' => [], 'applicants' => [], 'staffing_requests' => [], 'recruitment_requests' => []],
            default => ['scope' => 'candidate', 'applications' => [], 'saved_jobs' => []],
        };
    }
}
