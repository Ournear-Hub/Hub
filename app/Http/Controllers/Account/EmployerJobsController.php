<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreJobRequest;
use App\Http\Requests\Admin\UpdateJobRequest;
use App\Http\Requests\TransitionJobLifecycleRequest;
use App\Enums\ApplicationStatus;
use App\Enums\JobPublishingStatus;
use App\Models\Application;
use App\Models\EmploymentType;
use App\Models\EmployerProfile;
use App\Models\ExperienceRange;
use App\Models\Industry;
use App\Models\Job;
use App\Models\JobCategory;
use App\Models\Location;
use App\Services\EmployerPostingEligibilityService;
use App\Services\JobLifecycleService;
use App\Services\MasterData\MasterDataService;
use App\Support\EmployerApplicationPipeline;
use App\Support\EmployerVisibleCandidate;
use App\Services\CandidateApplicationViewService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EmployerJobsController extends Controller
{
    public function index(Request $request, EmployerPostingEligibilityService $eligibility): Response
    {
        $this->authorize('viewAny', Job::class);
        $profile = $this->profile($request);

        $listing = $request->string('listing')->toString();
        if (! in_array($listing, ['all', 'active', 'draft'], true)) {
            $listing = 'all';
        }

        $jobsQuery = $profile->jobs()->withCount('applications');
        if ($listing === 'active') {
            $jobsQuery->where('active', true);
        } elseif ($listing === 'draft') {
            $jobsQuery->where('publishing_status', 'draft');
        }

        $jobs = $jobsQuery
            ->latest()
            ->paginate(12)
            ->withQueryString()
            ->through(fn (Job $job) => $job->toAdminArray());

        return Inertia::render('Account/Employer/Jobs/Index', [
            'jobs' => $jobs,
            'listing' => $listing,
            'eligibility' => $eligibility->for($request->user()),
            'featured' => [
                'available' => false,
                'message' => 'Featured posting requires a verified per-post payment. Live payment gateway integration is not configured.',
            ],
        ]);
    }

    public function create(
        Request $request,
        MasterDataService $masterData,
        EmployerPostingEligibilityService $eligibility,
    ): Response {
        $this->authorize('create', Job::class);
        $profile = $this->profile($request);
        $posting = $eligibility->for($request->user());

        return Inertia::render('Account/Employer/Jobs/Form', [
            'job' => null,
            'company_name' => $profile->displayCompany(),
            'lookups' => $masterData->formOptions(),
            'eligibility' => $posting,
            'next_tier' => $profile->trial_job_used_at === null ? 'trial' : 'basic',
        ]);
    }

    public function store(
        StoreJobRequest $request,
        EmployerPostingEligibilityService $eligibility,
        JobLifecycleService $lifecycle,
    ): RedirectResponse {
        $validated = $request->validated();
        $status = JobPublishingStatus::tryFrom($validated['publishing_status'] ?? 'draft')
            ?? JobPublishingStatus::Draft;

        if ($status === JobPublishingStatus::Published && ! $eligibility->allows($request->user())) {
            throw ValidationException::withMessages([
                'posting' => $eligibility->for($request->user())['reason'],
            ]);
        }

        $job = DB::transaction(function () use ($request, $validated, $lifecycle): Job {
            $profile = EmployerProfile::query()
                ->where('user_id', $request->user()->id)
                ->lockForUpdate()
                ->firstOrFail();
            $data = $this->jobPayload($validated, $profile, lifecycle: $lifecycle);
            $status = $data['publishing_status'] instanceof JobPublishingStatus
                ? $data['publishing_status']
                : (JobPublishingStatus::tryFrom((string) ($data['publishing_status'] ?? 'draft'))
                    ?? JobPublishingStatus::Draft);

            if ($status === JobPublishingStatus::Published) {
                $isTrial = $profile->trial_job_used_at === null;
                $data['posting_tier'] = $isTrial ? 'trial' : 'basic';
                $data['trial_expires_at'] = $isTrial ? now()->addHour() : null;

                if ($isTrial) {
                    $profile->forceFill(['trial_job_used_at' => now()])->save();
                }
            } else {
                $data['posting_tier'] = 'basic';
                $data['trial_expires_at'] = null;
            }

            return Job::query()->create($data);
        });

        if ($job->publishing_status !== JobPublishingStatus::Published) {
            return redirect()
                ->route('account.employer.jobs.index')
                ->with('success', 'Job saved as draft.');
        }

        return redirect()
            ->route('account.employer.jobs.index')
            ->with('success', $job->posting_tier === 'trial'
                ? 'Trial job posted. It remains public for one hour.'
                : 'Basic job posted. Basic jobs are not featured.');
    }

    public function edit(
        Request $request,
        Job $job,
        MasterDataService $masterData,
        EmployerPostingEligibilityService $eligibility,
    ): Response {
        $this->authorize('update', $job);
        $profile = $this->profile($request);

        return Inertia::render('Account/Employer/Jobs/Form', [
            'job' => $job->toAdminArray(),
            'company_name' => $profile->displayCompany(),
            'lookups' => $masterData->formOptions(),
            'eligibility' => $eligibility->for($request->user()),
            'next_tier' => $job->posting_tier,
        ]);
    }

    public function update(UpdateJobRequest $request, Job $job): RedirectResponse
    {
        $this->authorize('update', $job);
        $job->update($this->jobPayload($request->validated(), $this->profile($request), $job, app(JobLifecycleService::class)));

        return redirect()
            ->route('account.employer.jobs.index')
            ->with('success', 'Job updated.');
    }

    public function lifecycle(
        TransitionJobLifecycleRequest $request,
        Job $job,
        JobLifecycleService $lifecycle,
    ): RedirectResponse {
        $this->authorize('update', $job);

        $job = $lifecycle->applyAction($job, $request->string('action')->toString(), $request->user());

        return back()->with('success', 'Job '.$job->statusLabel().'.');
    }

    public function destroy(Request $request, Job $job): RedirectResponse
    {
        $this->authorize('delete', $job);

        if ($job->applications()->exists()) {
            return back()->with('error', 'This job cannot be deleted because applications have been submitted.');
        }

        $job->delete();

        return redirect()
            ->route('account.employer.jobs.index')
            ->with('success', 'Job deleted.');
    }

    public function applicants(Request $request, Job $job): Response
    {
        $this->authorize('view', $job);

        $stageKeys = implode(',', array_column(EmployerApplicationPipeline::stages(), 'key'));

        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', 'string', 'in:'.implode(',', array_column(ApplicationStatus::cases(), 'value'))],
            'stage' => ['nullable', 'string', 'in:'.$stageKeys],
        ]);

        $search = trim((string) ($validated['q'] ?? ''));
        $status = $validated['status'] ?? null;
        $stage = trim((string) ($validated['stage'] ?? ''));
        if ($stage === 'all') {
            $stage = '';
        }

        $query = $job->applications()
            ->with(['user.candidateProfile', 'job']);

        if ($search !== '') {
            $like = '%'.$search.'%';
            $query->where(function ($builder) use ($like) {
                $builder->where('full_name', 'like', $like)
                    ->orWhereHas('user', fn ($user) => $user->where('name', 'like', $like));
            });
        }

        if ($stage !== '') {
            $stageStatuses = EmployerApplicationPipeline::statusesForStage($stage);
            if ($stageStatuses !== []) {
                $query->whereIn('status', array_map(fn (ApplicationStatus $case) => $case->value, $stageStatuses));
            }
        } elseif (is_string($status) && $status !== '') {
            $query->where('status', $status);
        }

        $applications = $query
            ->latest()
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Application $application) => EmployerVisibleCandidate::fromApplication($application));

        return Inertia::render('Account/Employer/Applicants/Index', [
            'job' => [
                'title' => $job->title,
                'business_id' => $job->business_id,
                'slug' => $job->slug,
                'location' => $job->displayLocation(),
                'status_label' => $job->statusLabel(),
            ],
            'applications' => $applications,
            'pipeline_summary' => EmployerApplicationPipeline::summaryForJob($job),
            'filters' => [
                'q' => $search,
                'status' => $status ?? '',
                'stage' => $stage,
            ],
            'status_options' => collect(ApplicationStatus::cases())
                ->map(fn (ApplicationStatus $case) => [
                    'value' => $case->value,
                    'label' => $case->toCandidateStatusLabel(),
                ])
                ->values()
                ->all(),
        ]);
    }

    public function showApplication(
        Request $request,
        Job $job,
        Application $application,
        CandidateApplicationViewService $applications,
    ): Response {
        $this->authorize('view', $job);
        abort_unless((int) $application->job_id === (int) $job->id, 404);

        $application->loadMissing(['user.candidateProfile', 'job', 'activities']);
        $candidate = EmployerVisibleCandidate::fromApplication($application);

        return Inertia::render('Account/Employer/Applications/Show', [
            'job' => [
                'title' => $job->title,
                'business_id' => $job->business_id,
                'slug' => $job->slug,
                'location' => $job->displayLocation(),
                'status_label' => $job->statusLabel(),
                'company' => $job->project,
            ],
            'application' => [
                ...$candidate,
                'timeline' => $applications->timeline($application),
            ],
            'list_filters' => [
                'q' => trim((string) $request->query('q', '')),
                'status' => trim((string) $request->query('status', '')),
                'stage' => trim((string) $request->query('stage', '')),
            ],
        ]);
    }

    public function resume(Request $request, Application $application): StreamedResponse
    {
        $application->loadMissing(['job', 'submissions']);
        $ownsJob = $application->job && $request->user()->can('view', $application->job);
        $ownsSubmission = EmployerVisibleCandidate::employerOwnsSubmission($request->user(), $application);
        abort_unless($ownsJob || $ownsSubmission, 403);

        $path = $application->getRawOriginal('resume_path');
        abort_unless(is_string($path) && $path !== '' && Storage::disk('resumes')->exists($path), 404);

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $label = $application->business_id ?: 'application-'.$application->id;
        $downloadName = $extension !== '' ? $label.'.'.$extension : $label;
        $wantsInline = $request->string('disposition')->toString() === 'inline';
        $canInline = $extension === 'pdf';
        $inline = $wantsInline && $canInline;
        $mime = match ($extension) {
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            default => 'application/octet-stream',
        };

        return Storage::disk('resumes')->response(
            $path,
            $downloadName,
            [
                'Content-Type' => $mime,
                'Content-Disposition' => sprintf(
                    '%s; filename="%s"',
                    $inline ? 'inline' : 'attachment',
                    str_replace('"', '', $downloadName)
                ),
            ],
            $inline ? 'inline' : 'attachment',
        );
    }

    private function profile(Request $request): EmployerProfile
    {
        $profile = $request->user()?->employerProfile;
        abort_unless($profile instanceof EmployerProfile, 403);

        return $profile;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function jobPayload(
        array $data,
        EmployerProfile $profile,
        ?Job $job = null,
        ?JobLifecycleService $lifecycle = null,
    ): array {
        unset($data['employer_id'], $data['featured'], $data['active']);

        $data['employer_id'] = $profile->id;
        $data['project'] = $profile->displayCompany();
        $data['featured'] = false;
        $data['urgent'] = (bool) ($data['urgent'] ?? false);
        $data['salary'] = $data['salary'] ?: null;
        $data['joining_date'] = $data['joining_date'] ?? null;
        $data['site'] = ($data['site'] ?? null) ?: null;
        $data['accommodation'] = ($data['accommodation'] ?? null) ?: null;
        $data['responsibilities'] = $data['responsibilities'] ?: null;
        $data['requirements'] = $data['requirements'] ?: null;
        $data['what_we_offer'] = $data['what_we_offer'] ?: null;

        foreach ([
            'industry_id' => [Industry::class, 'industry'],
            'job_category_id' => [JobCategory::class, 'category'],
            'location_id' => [Location::class, 'location'],
            'employment_type_id' => [EmploymentType::class, 'employment_type'],
            'experience_range_id' => [ExperienceRange::class, 'experience'],
        ] as $idField => [$model, $labelField]) {
            $id = $data[$idField] ?? null;
            if (filled($id)) {
                $data[$labelField] = $model::query()->whereKey($id)->value('name');
            } elseif ($job === null) {
                $data[$labelField] = '';
            } else {
                unset($data[$labelField]);
            }
        }

        if ($job === null) {
            $lifecycle ??= app(JobLifecycleService::class);
            $data['slug'] = Job::uniqueSlug($data['title']);
            $status = JobPublishingStatus::tryFrom((string) ($data['publishing_status'] ?? 'draft'))
                ?? JobPublishingStatus::Draft;
            $data['publishing_status'] = $status->value;
            $data['active'] = $lifecycle->syncActiveForStatus($status, (bool) ($data['active'] ?? false));
        } else {
            unset($data['posting_tier'], $data['trial_expires_at'], $data['publishing_status'], $data['active']);
        }

        return $data;
    }
}
