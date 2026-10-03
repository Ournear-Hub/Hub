<?php

namespace App\Http\Controllers\Account;

use App\Enums\EmployerSubmissionStatus;
use App\Http\Controllers\Controller;
use App\Models\EmployerProfile;
use App\Models\EmployerSubmission;
use App\Models\Job;
use App\Support\EmployerRecruitmentPartnership;
use App\Support\EmployerVisibleCandidate;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EmployerSubmissionsController extends Controller
{
    public function index(Request $request): Response
    {
        $profile = $this->profile($request);

        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', 'string', 'in:'.implode(',', array_column(EmployerSubmissionStatus::cases(), 'value'))],
            'job' => ['nullable', 'string', 'max:120'],
        ]);

        $search = trim((string) ($validated['q'] ?? ''));
        $status = $validated['status'] ?? null;
        $jobFilter = trim((string) ($validated['job'] ?? ''));

        $query = EmployerRecruitmentPartnership::ownedSubmissionsQuery($profile);

        if ($search !== '') {
            $like = '%'.$search.'%';
            $query->where(function ($builder) use ($like) {
                $builder->whereHas('application', fn ($application) => $application
                    ->where('full_name', 'like', $like)
                    ->orWhereHas('user', fn ($user) => $user->where('name', 'like', $like)))
                    ->orWhereHas('job', fn ($job) => $job->where('title', 'like', $like));
            });
        }

        if (is_string($status) && $status !== '') {
            $query->where('status', $status);
        }

        if ($jobFilter !== '') {
            $query->whereHas('job', fn ($job) => $job->where('slug', $jobFilter));
        }

        $submissions = $query
            ->latest('submitted_at')
            ->latest('id')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (EmployerSubmission $submission) => EmployerVisibleCandidate::fromSubmission($submission));

        $recentSubmissions = EmployerRecruitmentPartnership::ownedSubmissionsQuery($profile)
            ->latest('submitted_at')
            ->latest('id')
            ->limit(5)
            ->get()
            ->map(fn (EmployerSubmission $submission) => EmployerVisibleCandidate::fromSubmission($submission))
            ->values()
            ->all();

        return Inertia::render('Account/Employer/Submissions/Index', [
            'company' => [
                'name' => $profile->displayCompany(),
                'business_id' => $profile->business_id,
            ],
            'summary' => EmployerRecruitmentPartnership::workspaceSummary($profile),
            'requirements' => EmployerRecruitmentPartnership::activeRequirements($profile),
            'recent_submissions' => $recentSubmissions,
            'submissions' => $submissions,
            'filters' => [
                'q' => $search,
                'status' => $status ?? '',
                'job' => $jobFilter,
            ],
            'status_options' => collect(EmployerSubmissionStatus::cases())
                ->map(fn (EmployerSubmissionStatus $case) => [
                    'value' => $case->value,
                    'label' => \App\Support\RecruitmentWorkspace::responseLabel($case),
                ])
                ->values()
                ->all(),
            'support_href' => EmployerRecruitmentPartnership::SUPPORT_CONTACT_HREF,
        ]);
    }

    public function showRequirement(Request $request, Job $job): Response
    {
        $profile = $this->profile($request);
        abort_unless((int) $job->employer_id === (int) $profile->id, 404);

        $hasSubmissions = EmployerSubmission::query()
            ->where('employer_profile_id', $profile->id)
            ->where('job_id', $job->id)
            ->exists();
        abort_unless($hasSubmissions, 404);

        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', 'string', 'in:'.implode(',', array_column(EmployerSubmissionStatus::cases(), 'value'))],
        ]);

        $search = trim((string) ($validated['q'] ?? ''));
        $status = $validated['status'] ?? null;

        $query = EmployerRecruitmentPartnership::ownedSubmissionsQuery($profile)
            ->where('job_id', $job->id);

        if ($search !== '') {
            $like = '%'.$search.'%';
            $query->where(function ($builder) use ($like) {
                $builder->whereHas('application', fn ($application) => $application
                    ->where('full_name', 'like', $like)
                    ->orWhereHas('user', fn ($user) => $user->where('name', 'like', $like)));
            });
        }

        if (is_string($status) && $status !== '') {
            $query->where('status', $status);
        }

        $submissions = $query
            ->latest('submitted_at')
            ->latest('id')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (EmployerSubmission $submission) => EmployerVisibleCandidate::fromSubmission($submission));

        $requirementSummary = collect(EmployerRecruitmentPartnership::activeRequirements($profile))
            ->firstWhere('job_id', $job->id);

        return Inertia::render('Account/Employer/Submissions/Requirement', [
            'company' => [
                'name' => $profile->displayCompany(),
                'business_id' => $profile->business_id,
            ],
            'requirement' => $requirementSummary ?? [
                'job_id' => $job->id,
                'title' => $job->title,
                'slug' => $job->slug,
                'business_id' => $job->business_id,
                'location' => $job->displayLocation(),
                'vacancy' => $job->vacancy,
                'created_at' => $job->created_at?->toDateTimeString(),
                'job_status_label' => $job->statusLabel(),
                'submission_count' => $submissions->total(),
                'status_label' => null,
                'status_counts' => [],
                'latest_submitted_at' => null,
                'href' => route('account.employer.submissions.requirement', $job, false),
                'next_action' => null,
            ],
            'submissions' => $submissions,
            'filters' => [
                'q' => $search,
                'status' => $status ?? '',
            ],
            'status_options' => collect(EmployerSubmissionStatus::cases())
                ->map(fn (EmployerSubmissionStatus $case) => [
                    'value' => $case->value,
                    'label' => \App\Support\RecruitmentWorkspace::responseLabel($case),
                ])
                ->values()
                ->all(),
            'support_href' => EmployerRecruitmentPartnership::SUPPORT_CONTACT_HREF,
        ]);
    }

    public function show(Request $request, EmployerSubmission $submission): Response
    {
        $profile = $this->profile($request);
        abort_unless((int) $submission->employer_profile_id === (int) $profile->id, 404);

        $submission->loadMissing(['application.user.candidateProfile', 'job', 'activities']);
        $candidate = EmployerVisibleCandidate::fromSubmission($submission);

        $listFilters = [
            'q' => trim((string) $request->query('q', '')),
            'status' => trim((string) $request->query('status', '')),
            'job' => $submission->job?->slug ?? '',
        ];

        return Inertia::render('Account/Employer/Submissions/Show', [
            'company' => [
                'name' => $profile->displayCompany(),
                'business_id' => $profile->business_id,
            ],
            'submission' => [
                ...$candidate,
                'timeline' => EmployerRecruitmentPartnership::employerTimeline($submission),
            ],
            'list_filters' => $listFilters,
            'support_href' => EmployerRecruitmentPartnership::SUPPORT_CONTACT_HREF,
        ]);
    }

    private function profile(Request $request): EmployerProfile
    {
        $profile = $request->user()?->employerProfile;
        abort_unless($profile instanceof EmployerProfile, 403);

        return $profile;
    }
}
