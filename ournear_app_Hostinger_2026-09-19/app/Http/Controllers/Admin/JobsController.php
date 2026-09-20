<?php

namespace App\Http\Controllers\Admin;

use App\Enums\JobPublishingStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreJobRequest;
use App\Http\Requests\Admin\UpdateJobRequest;
use App\Models\Job;
use App\Support\JobListingQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class JobsController extends Controller
{
    public function index(Request $request, JobListingQuery $jobs): Response
    {
        $this->authorize('viewAny', Job::class);

        $listings = $jobs->adminIndex($request)->through(fn (Job $job) => $job->toAdminArray());

        return Inertia::render('Admin/Jobs/Index', [
            'jobs' => $listings,
            'filters' => [
                'search' => $request->string('search')->toString(),
                'status' => $request->string('status')->toString(),
                'industry' => $request->string('industry')->toString(),
                'category' => $request->string('category')->toString(),
                'location' => $request->string('location')->toString(),
                'featured' => $request->string('featured')->toString(),
                'active' => $request->string('active')->toString(),
            ],
            'options' => $jobs->adminFilterOptions(),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Job::class);

        return Inertia::render('Admin/Jobs/Form', [
            'job' => null,
        ]);
    }

    public function store(StoreJobRequest $request): RedirectResponse
    {
        $job = Job::query()->create($this->payload($request));

        return redirect()
            ->route('admin.jobs.show', $job)
            ->with('success', 'Job created.');
    }

    public function show(Job $job): Response
    {
        $this->authorize('view', $job);

        $job->loadCount('applications');

        return Inertia::render('Admin/Jobs/Show', [
            'job' => $job->toAdminArray(),
        ]);
    }

    public function edit(Job $job): Response
    {
        $this->authorize('update', $job);

        return Inertia::render('Admin/Jobs/Form', [
            'job' => $job->toAdminArray(),
        ]);
    }

    public function update(UpdateJobRequest $request, Job $job): RedirectResponse
    {
        $job->update($this->payload($request, $job));

        return redirect()
            ->route('admin.jobs.show', $job)
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

        if ($job === null) {
            $data['slug'] = Job::uniqueSlug($data['title']);
        }

        return $data;
    }
}
