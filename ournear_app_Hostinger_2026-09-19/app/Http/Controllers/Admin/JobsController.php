<?php

namespace App\Http\Controllers\Admin;

use App\Enums\JobPublishingStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreJobRequest;
use App\Http\Requests\Admin\UpdateJobRequest;
use App\Models\EmploymentType;
use App\Models\ExperienceRange;
use App\Models\Industry;
use App\Models\Job;
use App\Models\JobCategory;
use App\Models\Location;
use App\Services\MasterData\MasterDataService;
use App\Support\JobListingQuery;
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

        return Inertia::render('Admin/Jobs/Index', [
            'jobs' => $listings,
            'selected' => $selected?->toAdminArray(),
            'applications' => $selected
                ? $selected->applications()->latest()->limit(10)->get()->map->toAdminListArray()->values()->all()
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
                'job' => $selected?->slug ?? '',
                'mode' => $mode === 'view' ? '' : $mode,
            ],
            'lookups' => $masterData->formOptions(),
        ]);
    }

    public function create(Request $request, JobListingQuery $jobs, MasterDataService $masterData): Response
    {
        $request->merge(['mode' => 'create']);

        return $this->index($request, $jobs, $masterData);
    }

    public function store(StoreJobRequest $request): RedirectResponse
    {
        $job = Job::query()->create($this->payload($request));

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

    public function update(UpdateJobRequest $request, Job $job): RedirectResponse
    {
        $job->update($this->payload($request, $job));

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

    public function togglePublish(Job $job): RedirectResponse
    {
        $this->authorize('update', $job);

        $job->publishing_status = $job->publishing_status === JobPublishingStatus::Published
            ? JobPublishingStatus::Draft
            : JobPublishingStatus::Published;
        $job->save();

        return back()->with('success', $job->publishing_status === JobPublishingStatus::Published
            ? 'Job published.'
            : 'Job unpublished.');
    }

    public function toggleActive(Job $job): RedirectResponse
    {
        $this->authorize('update', $job);

        $job->active = ! $job->active;
        $job->save();

        return back()->with('success', $job->active ? 'Job activated.' : 'Job deactivated.');
    }

    public function toggleFeatured(Job $job): RedirectResponse
    {
        $this->authorize('update', $job);

        $job->featured = ! $job->featured;
        $job->save();

        return back()->with('success', $job->featured ? 'Job marked as featured.' : 'Job unmarked as featured.');
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(StoreJobRequest|UpdateJobRequest $request, ?Job $job = null): array
    {
        $data = $request->validated();
        $data['featured'] = $request->boolean('featured');
        $data['active'] = $request->boolean('active');
        $data['salary'] = $data['salary'] ?: null;
        $data['responsibilities'] = $data['responsibilities'] ?: null;
        $data['requirements'] = $data['requirements'] ?: null;
        $data['what_we_offer'] = $data['what_we_offer'] ?: null;

        $this->syncMasterString($data, 'industry_id', 'industry', Industry::class, $job);
        $this->syncMasterString($data, 'job_category_id', 'category', JobCategory::class, $job);
        $this->syncMasterString($data, 'location_id', 'location', Location::class, $job);
        $this->syncMasterString($data, 'employment_type_id', 'employment_type', EmploymentType::class, $job);
        $this->syncMasterString($data, 'experience_range_id', 'experience', ExperienceRange::class, $job);

        if (! filled($data['job_title_id'] ?? null)) {
            $data['job_title_id'] = null;
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
            ->withCount('applications')
            ->where('slug', $slug)
            ->first();

        if ($job && $request->user()?->can('view', $job)) {
            return $job;
        }

        return null;
    }
}
