<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreJobRequest;
use App\Http\Requests\Admin\UpdateJobRequest;
use App\Models\Application;
use App\Models\EmploymentType;
use App\Models\EmployerProfile;
use App\Models\ExperienceRange;
use App\Models\Industry;
use App\Models\Job;
use App\Models\JobCategory;
use App\Models\Location;
use App\Services\EmployerPostingEligibilityService;
use App\Services\MasterData\MasterDataService;
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

        $jobs = $profile->jobs()
            ->withCount('applications')
            ->latest()
            ->paginate(12)
            ->withQueryString()
            ->through(fn (Job $job) => $job->toAdminArray());

        return Inertia::render('Account/Employer/Jobs/Index', [
            'jobs' => $jobs,
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
    ): RedirectResponse {
        if (! $eligibility->allows($request->user())) {
            throw ValidationException::withMessages([
                'posting' => $eligibility->for($request->user())['reason'],
            ]);
        }

        $job = DB::transaction(function () use ($request): Job {
            $profile = EmployerProfile::query()
                ->where('user_id', $request->user()->id)
                ->lockForUpdate()
                ->firstOrFail();
            $isTrial = $profile->trial_job_used_at === null;
            $data = $this->jobPayload($request->validated(), $profile);
            $data['posting_tier'] = $isTrial ? 'trial' : 'basic';
            $data['trial_expires_at'] = $isTrial ? now()->addHour() : null;

            $job = Job::query()->create($data);

            if ($isTrial) {
                $profile->forceFill(['trial_job_used_at' => now()])->save();
            }

            return $job;
        });

        return redirect()
            ->route('account.employer.jobs.index')
            ->with('success', $job->posting_tier === 'trial'
                ? 'Trial job posted. It remains public for one hour.'
                : 'Basic job posted. Basic jobs are not featured.');
    }

    public function edit(Request $request, Job $job, MasterDataService $masterData): Response
    {
        $this->authorize('update', $job);
        $profile = $this->profile($request);

        return Inertia::render('Account/Employer/Jobs/Form', [
            'job' => $job->toAdminArray(),
            'company_name' => $profile->displayCompany(),
            'lookups' => $masterData->formOptions(),
            'eligibility' => null,
            'next_tier' => $job->posting_tier,
        ]);
    }

    public function update(UpdateJobRequest $request, Job $job): RedirectResponse
    {
        $this->authorize('update', $job);
        $job->update($this->jobPayload($request->validated(), $this->profile($request), $job));

        return redirect()
            ->route('account.employer.jobs.index')
            ->with('success', 'Job updated.');
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

        $applications = $job->applications()
            ->with(['user.candidateProfile'])
            ->latest()
            ->get()
            ->map(function (Application $application) {
                $user = $application->user;
                $profile = $user?->candidateProfile;

                return [
                    'id' => $application->id,
                    'business_id' => $application->business_id,
                    'name' => $user?->name ?: $application->full_name,
                    'photo_url' => $user?->photoUrl(),
                    'username' => $user?->username,
                    'designation' => $profile?->current_designation,
                    'location' => $profile?->location ?: $application->current_location,
                    'experience' => $profile?->experience ?: $application->experience,
                    'qualification' => $profile?->qualification ?: $application->qualification,
                    'status' => $application->status->value,
                    'applied_at' => $application->created_at?->toDateTimeString(),
                    'has_resume' => filled($application->getRawOriginal('resume_path')),
                    'resume_url' => route('account.employer.applications.resume', $application, false),
                    'public_profile_url' => $user
                        ? route('profiles.show', ['user' => $user->username], false)
                        : null,
                ];
            })
            ->values()
            ->all();

        return Inertia::render('Account/Employer/Applicants/Index', [
            'job' => [
                'title' => $job->title,
                'business_id' => $job->business_id,
            ],
            'applications' => $applications,
        ]);
    }

    public function resume(Request $request, Application $application): StreamedResponse
    {
        $application->loadMissing('job');
        abort_unless($application->job && $request->user()->can('view', $application->job), 403);

        $path = $application->getRawOriginal('resume_path');
        abort_unless(is_string($path) && $path !== '' && Storage::disk('resumes')->exists($path), 404);

        return Storage::disk('resumes')->download($path);
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
    private function jobPayload(array $data, EmployerProfile $profile, ?Job $job = null): array
    {
        unset($data['employer_id'], $data['featured'], $data['active']);

        $data['employer_id'] = $profile->id;
        $data['project'] = $profile->displayCompany();
        $data['featured'] = false;
        $data['active'] = true;
        $data['publishing_status'] = 'published';
        $data['salary'] = $data['salary'] ?: null;
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
            $data['slug'] = Job::uniqueSlug($data['title']);
        } else {
            unset($data['posting_tier'], $data['trial_expires_at']);
        }

        return $data;
    }
}
