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
use App\Services\EmployerApplicantCompareService;
use App\Services\EmployerJobPublicationPolicy;
use App\Services\EmployerPostingEligibilityService;
use App\Services\JobLifecycleService;
use App\Services\JobListingRequirementService;
use App\Services\MasterData\MasterDataService;
use App\Support\EmployerApplicationPipeline;
use App\Support\EmployerVisibleCandidate;
use App\Services\ApplicationResumeDeliveryService;
use App\Services\CandidateApplicationViewService;
use App\Support\EmployerApplicationAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
            'company_industry_id' => $profile->industry_id,
            'company_industry_name' => $profile->displayIndustry(),
            'lookups' => $masterData->formOptions(),
            'eligibility' => $posting,
            'next_tier' => $posting['unlimited_publishing'] || $posting['trial_available']
                ? ($posting['trial_available'] ? 'trial' : 'basic')
                : 'basic',
        ]);
    }

    public function store(
        StoreJobRequest $request,
        EmployerPostingEligibilityService $eligibility,
        EmployerJobPublicationPolicy $publicationPolicy,
        JobLifecycleService $lifecycle,
        JobListingRequirementService $requirements,
    ): RedirectResponse {
        $validated = $request->validated();
        $status = JobPublishingStatus::tryFrom($validated['publishing_status'] ?? 'draft')
            ?? JobPublishingStatus::Draft;

        if ($status === JobPublishingStatus::Published && ! $eligibility->allows($request->user())) {
            throw ValidationException::withMessages([
                'posting' => $eligibility->for($request->user())['reason'],
            ]);
        }

        $job = DB::transaction(function () use ($request, $validated, $lifecycle, $publicationPolicy, $requirements): Job {
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
                $package = $publicationPolicy->packageForPublish($profile);
                $data['posting_tier'] = $package['posting_tier'];
                $data['trial_expires_at'] = $package['trial_expires_at'];

                if ($package['mark_trial_used']) {
                    $profile->forceFill(['trial_job_used_at' => now()])->save();
                }
            } else {
                $data['posting_tier'] = 'basic';
                $data['trial_expires_at'] = null;
            }

            $created = Job::query()->create($data);
            $requirements->sync(
                $created,
                $request->input('qualification_ids'),
                $request->input('skill_ids'),
            );

            return $created;
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
            'company_industry_id' => $profile->industry_id,
            'company_industry_name' => $profile->displayIndustry(),
            'lookups' => $masterData->formOptions(),
            'eligibility' => $eligibility->for($request->user()),
            'next_tier' => $job->posting_tier,
        ]);
    }

    public function update(
        UpdateJobRequest $request,
        Job $job,
        JobListingRequirementService $requirements,
    ): RedirectResponse {
        $this->authorize('update', $job);
        $job->update($this->jobPayload($request->validated(), $this->profile($request), $job, app(JobLifecycleService::class)));
        $requirements->sync(
            $job,
            $request->input('qualification_ids'),
            $request->input('skill_ids'),
        );

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

    public function applicants(Request $request, Job $job, EmployerApplicantCompareService $compareService): Response
    {
        $this->authorize('view', $job);

        $stageKeys = implode(',', array_unique(array_merge(
            EmployerApplicationPipeline::employerJobAtsStageKeys(),
            ['submitted', 'under_review', 'rejected', 'withdrawn'],
        )));

        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', 'string', 'in:'.implode(',', array_column(ApplicationStatus::cases(), 'value'))],
            'stage' => ['nullable', 'string', 'in:'.$stageKeys],
            'compare' => ['nullable', 'string', 'max:120'],
        ]);

        $search = trim((string) ($validated['q'] ?? ''));
        $status = $validated['status'] ?? null;
        $stage = EmployerApplicationPipeline::normalizeLegacyJobStageKey(trim((string) ($validated['stage'] ?? '')));
        if ($stage === 'all') {
            $stage = '';
        }

        $query = $job->applications()
            ->with(['user.candidateProfile', 'job', 'placements', 'submissions', 'screening', 'interviews']);

        if ($search !== '') {
            $like = '%'.$search.'%';
            $query->where(function ($builder) use ($like) {
                $builder->where('full_name', 'like', $like)
                    ->orWhereHas('user', fn ($user) => $user->where('name', 'like', $like));
            });
        }

        if ($stage !== '') {
            EmployerApplicationPipeline::applyEmployerJobAtsStageFilter($query, $job, $stage);
        } elseif (is_string($status) && $status !== '') {
            $query->where('status', $status);
        }

        $applications = $query
            ->latest()
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Application $application) => EmployerVisibleCandidate::fromApplication($application));

        $compareRaw = trim((string) ($validated['compare'] ?? ''));
        $compareIds = $compareService->parseCompareIds($compareRaw !== '' ? $compareRaw : null);
        $compareApplications = [];
        $compareOpen = false;
        if ($compareIds !== []) {
            if (count($compareIds) < 2) {
                throw ValidationException::withMessages([
                    'compare' => 'Select at least two applicants to compare.',
                ]);
            }

            $compareApplications = $compareService->rowsForJob($job, $compareIds);
            $compareOpen = count($compareApplications) >= 2;
        }

        return Inertia::render('Account/Employer/Applicants/Index', [
            'job' => [
                'title' => $job->title,
                'business_id' => $job->business_id,
                'slug' => $job->slug,
                'location' => $job->displayLocation(),
                'status_label' => $job->statusLabel(),
            ],
            'applications' => $applications,
            'pipeline_summary' => EmployerApplicationPipeline::summaryForJobAts($job),
            'filters' => [
                'q' => $search,
                'status' => $status ?? '',
                'stage' => $stage,
                'compare' => $compareRaw,
            ],
            'compare' => [
                'open' => $compareOpen,
                'application_ids' => $compareIds,
                'applications' => $compareApplications,
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
        \App\Services\AccountApplicationRecruitmentService $recruitment,
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
            'recruitment' => $recruitment->employerWorkflow($application, $request->user()),
        ]);
    }

    public function resume(
        Request $request,
        Application $application,
        ApplicationResumeDeliveryService $resumes,
    ): StreamedResponse {
        $user = $request->user();
        abort_unless($user !== null, 403);

        $application->loadMissing(['job', 'submissions']);
        $authorized = EmployerApplicationAccess::managesApplication($user, $application)
            || EmployerVisibleCandidate::employerOwnsSubmission($user, $application);
        abort_unless($authorized, 403);

        $label = $application->business_id ?: 'application-'.$application->id;

        return $resumes->stream($request, $application, $label);
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

        $project = trim((string) ($data['project'] ?? ''));
        $data['project'] = $project !== '' ? $project : $profile->displayCompany();

        if ($profile->industry_id) {
            $data['industry_id'] = $profile->industry_id;
            $data['industry'] = Industry::query()->whereKey($profile->industry_id)->value('name') ?? '';
        }
        $data['featured'] = false;
        $priority = (string) ($data['listing_priority'] ?? 'standard');
        if (! in_array($priority, ['standard', 'urgent', 'immediate_requirement'], true)) {
            $priority = 'standard';
        }
        $data['listing_priority'] = $priority;
        $data['urgent'] = in_array($priority, ['urgent', 'immediate_requirement'], true);
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
            if ($job !== null && ! array_key_exists($idField, $data)) {
                unset($data[$idField], $data[$labelField]);

                continue;
            }

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
