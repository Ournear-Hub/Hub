<?php

namespace App\Http\Controllers\Admin;

use App\Enums\JobPublishingStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreJobRequest;
use App\Http\Requests\Admin\UpdateJobRequest;
use App\Http\Requests\TransitionJobLifecycleRequest;
use App\Enums\RecruitmentActivityType;
use App\Models\EmploymentType;
use App\Models\EmployerProfile;
use App\Models\ExperienceRange;
use App\Models\Industry;
use App\Models\Job;
use App\Models\JobCategory;
use App\Models\Location;
use App\Models\Task;
use App\Services\JobLifecycleService;
use App\Services\JobListingRequirementService;
use App\Services\MasterData\MasterDataService;
use App\Services\RecruitmentActivityService;
use App\Support\JobListingQuery;
use App\Support\StaffOptions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class JobsController extends Controller
{
    public function index(Request $request, JobListingQuery $jobs, MasterDataService $masterData): Response
    {
        $this->authorize('viewAny', Job::class);

        $listings = $jobs->adminIndex($request)->through(fn (Job $job) => $job->toAdminArray());

        $selected = $this->selectedJob($request);
        $mode = $request->string('mode')->toString();
        if (! in_array($mode, ['create', 'edit'], true)) {
            $mode = $selected ? 'view' : '';
        }
        if ($mode === 'edit' && $selected === null) {
            $mode = '';
        }
        if ($mode === 'create') {
            $this->authorize('create', Job::class);
            $selected = null;
        }

        $selectedPayload = $selected?->toAdminArray();
        if (is_array($selectedPayload) && $selected) {
            $selectedPayload['links'] = [
                'applications' => route('admin.applications.index', ['job_id' => $selected->id], false),
                'candidates' => route('admin.candidates.index', ['job_id' => $selected->id], false),
                'screening' => route('admin.screening.index', ['job_id' => $selected->id], false),
                'shortlisted' => route('admin.shortlisted.index', ['job_id' => $selected->id], false),
                'submissions' => route('admin.submissions.index', ['job_id' => $selected->id], false),
                'placements' => route('admin.placements.index', ['job_id' => $selected->id], false),
            ];
        }

        return Inertia::render('Admin/Jobs/Index', [
            'jobs' => $listings,
            'selected' => $selectedPayload,
            'applications' => $selected
                ? $selected->applications()->with(['job', 'screening'])->latest()->limit(10)->get()->map->toAdminListArray()->values()->all()
                : [],
            'mode' => $mode,
            'filters' => [
                'search' => $request->string('search')->toString(),
                'status' => $request->string('status')->toString(),
                'industry_id' => $request->string('industry_id')->toString(),
                'job_category_id' => $request->string('job_category_id')->toString(),
                'job_title_id' => $request->string('job_title_id')->toString(),
                'location_id' => $request->string('location_id')->toString(),
                'employment_type_id' => $request->string('employment_type_id')->toString(),
                'experience_range_id' => $request->string('experience_range_id')->toString(),
                'featured' => $request->string('featured')->toString(),
                'active' => $request->string('active')->toString(),
                'employer_id' => $request->string('employer_id')->toString(),
                'job' => $selected?->slug ?? '',
                'mode' => $mode === 'view' ? '' : $mode,
            ],
            'lookups' => [
                ...$masterData->formOptions(),
                'employers' => EmployerProfile::query()
                    ->with('user')
                    ->latest()
                    ->get()
                    ->map(fn (EmployerProfile $employer) => $employer->toSelectArray())
                    ->values()
                    ->all(),
            ],
            'staffUsers' => StaffOptions::users(),
            'can_create_task' => $request->user()?->can('create', Task::class) === true,
        ]);
    }

    public function create(Request $request, JobListingQuery $jobs, MasterDataService $masterData): Response
    {
        $request->merge(['mode' => 'create']);

        return $this->index($request, $jobs, $masterData);
    }

    public function store(
        StoreJobRequest $request,
        RecruitmentActivityService $activities,
        JobListingRequirementService $requirements,
    ): RedirectResponse {
        $job = Job::query()->create($this->payload($request));
        $requirements->sync(
            $job,
            $request->input('qualification_ids'),
            $request->input('skill_ids'),
        );
        $this->recordEmployerAssignment($job, null, $request->user(), $activities);

        return redirect()
            ->route('admin.jobs.index', ['job' => $job->slug])
            ->with('success', 'Job created.');
    }

    public function show(Request $request, Job $job, JobListingQuery $jobs, MasterDataService $masterData): Response
    {
        $this->authorize('view', $job);
        $request->merge(['job' => $job->slug]);

        return $this->index($request, $jobs, $masterData);
    }

    public function edit(Request $request, Job $job, JobListingQuery $jobs, MasterDataService $masterData): Response
    {
        $this->authorize('update', $job);
        $request->merge(['job' => $job->slug, 'mode' => 'edit']);

        return $this->index($request, $jobs, $masterData);
    }

    public function update(
        UpdateJobRequest $request,
        Job $job,
        RecruitmentActivityService $activities,
        JobLifecycleService $lifecycle,
        JobListingRequirementService $requirements,
    ): RedirectResponse {
        $previousEmployerId = $job->employer_id;
        $previousStatus = $job->publishing_status;
        $data = $this->payload($request, $job);
        $target = $data['publishing_status'] ?? $job->publishing_status;
        unset($data['publishing_status'], $data['active']);
        $job->update($data);
        $requirements->sync(
            $job,
            $request->input('qualification_ids'),
            $request->input('skill_ids'),
        );

        $targetStatus = $target instanceof JobPublishingStatus
            ? $target
            : JobPublishingStatus::tryFrom((string) $target);
        if ($targetStatus instanceof JobPublishingStatus && $targetStatus !== $previousStatus) {
            $lifecycle->transition($job->fresh() ?? $job, $targetStatus, $request->user());
        }

        $this->recordEmployerAssignment($job->fresh() ?? $job, $previousEmployerId, $request->user(), $activities);

        return redirect()
            ->route('admin.jobs.index', ['job' => $job->slug])
            ->with('success', 'Job updated.');
    }

    public function destroy(Job $job): RedirectResponse
    {
        $this->authorize('delete', $job);

        if ($job->applications()->exists()) {
            return back()->with('error', 'This job cannot be deleted because applications have been submitted.');
        }

        $job->delete();

        return redirect()
            ->route('admin.jobs.index')
            ->with('success', 'Job deleted.');
    }

    public function togglePublish(Job $job, JobLifecycleService $lifecycle): RedirectResponse
    {
        $this->authorize('update', $job);
        $job = $lifecycle->togglePublish($job, request()->user());

        return back()->with('success', $job->publishing_status === JobPublishingStatus::Published
            ? 'Job published.'
            : 'Job unpublished.');
    }

    public function toggleActive(Job $job, JobLifecycleService $lifecycle): RedirectResponse
    {
        $this->authorize('update', $job);
        $job = $lifecycle->toggleActive($job, request()->user());

        return back()->with('success', $job->active ? 'Job activated.' : 'Job deactivated.');
    }

    public function toggleFeatured(Job $job): RedirectResponse
    {
        $this->authorize('update', $job);

        $job->featured = ! $job->featured;
        $job->save();

        return back()->with('success', $job->featured ? 'Job marked as featured.' : 'Job unmarked as featured.');
    }

    public function lifecycle(
        TransitionJobLifecycleRequest $request,
        Job $job,
        JobLifecycleService $lifecycle,
    ): RedirectResponse {
        $job = $lifecycle->applyAction($job, $request->string('action')->toString(), $request->user());

        return back()->with('success', 'Job '.$job->statusLabel().'.');
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(StoreJobRequest|UpdateJobRequest $request, ?Job $job = null): array
    {
        $data = $request->validated();
        $data['featured'] = $request->boolean('featured');
        $data['urgent'] = $request->boolean('urgent');
        $data['salary'] = $data['salary'] ?: null;
        $data['joining_date'] = $data['joining_date'] ?? null;
        $data['site'] = ($data['site'] ?? null) ?: null;
        $data['accommodation'] = ($data['accommodation'] ?? null) ?: null;
        $data['responsibilities'] = $data['responsibilities'] ?: null;
        $data['requirements'] = $data['requirements'] ?: null;
        $data['what_we_offer'] = $data['what_we_offer'] ?: null;

        $status = $data['publishing_status'] ?? null;
        if ($status instanceof JobPublishingStatus || is_string($status)) {
            $enum = $status instanceof JobPublishingStatus
                ? $status
                : JobPublishingStatus::tryFrom($status);
            if ($enum instanceof JobPublishingStatus) {
                $data['active'] = app(JobLifecycleService::class)->syncActiveForStatus(
                    $enum,
                    $request->boolean('active'),
                );
            } else {
                $data['active'] = $request->boolean('active');
            }
        } else {
            $data['active'] = $request->boolean('active');
        }

        $this->syncMasterString($data, 'industry_id', 'industry', Industry::class, $job);
        $this->syncMasterString($data, 'job_category_id', 'category', JobCategory::class, $job);
        $this->syncMasterString($data, 'location_id', 'location', Location::class, $job);
        $this->syncMasterString($data, 'employment_type_id', 'employment_type', EmploymentType::class, $job);
        $this->syncMasterString($data, 'experience_range_id', 'experience', ExperienceRange::class, $job);

        if (! filled($data['job_title_id'] ?? null)) {
            $data['job_title_id'] = null;
        }

        if ($request->exists('employer_id')) {
            $data['employer_id'] = filled($request->input('employer_id')) ? $request->integer('employer_id') : null;
        } elseif ($job !== null) {
            unset($data['employer_id']);
        } else {
            $data['employer_id'] = null;
        }

        if ($job === null) {
            $data['slug'] = Job::uniqueSlug($data['title']);
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  class-string  $model
     */
    private function syncMasterString(array &$data, string $idKey, string $stringKey, string $model, ?Job $job): void
    {
        if ($job !== null && ! array_key_exists($idKey, $data)) {
            unset($data[$idKey], $data[$stringKey]);

            return;
        }

        $id = $data[$idKey] ?? null;

        if (filled($id)) {
            $record = $model::query()->find($id);
            if ($record) {
                $data[$stringKey] = $record->name;
            }

            return;
        }

        $data[$idKey] = null;

        if ($job) {
            unset($data[$stringKey]);
        }
    }

    private function selectedJob(Request $request): ?Job
    {
        $slug = trim($request->string('job')->toString());
        if ($slug === '') {
            return null;
        }

        $job = Job::query()
            ->with(['employer.user'])
            ->withCount('applications')
            ->where('slug', $slug)
            ->first();

        if ($job && $request->user()?->can('view', $job)) {
            return $job;
        }

        return null;
    }

    private function recordEmployerAssignment(
        Job $job,
        ?int $previousEmployerId,
        $actor,
        RecruitmentActivityService $activities,
    ): void {
        $job->loadMissing('employer');
        $current = $job->employer_id;

        if ((int) $current === (int) $previousEmployerId) {
            return;
        }

        if ($current === null || $job->employer === null || $actor === null) {
            return;
        }

        $activities->record(
            actor: $actor,
            type: RecruitmentActivityType::EmployerAssigned,
            body: 'Assigned employer '.$job->employer->displayCompany().' to job '.$job->title.'.',
            employer: $job->employer,
        );
    }
}
