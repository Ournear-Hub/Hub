<?php

namespace App\Http\Controllers\Account;

use App\Enums\JobListingScope;
use App\Enums\JobPublishingStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreJobRequest;
use App\Models\EmployerProfile;
use App\Models\Job;
use App\Services\EmployerJobPublicationPolicy;
use App\Services\EmployerPostingEligibilityService;
use App\Services\JobListingRequirementService;
use App\Services\JobLifecycleService;
use App\Services\MasterData\MasterDataService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class RecruiterTalentPoolJobsController extends Controller
{
    public function index(Request $request): Response
    {
        abort(403, 'Create Job for Talent Pool is available in Admin → Talent Pool only.');
        $this->authorize('viewAny', Job::class);
        $profile = $this->profile($request);

        $jobs = $profile->jobs()
            ->whereIn('listing_scope', [
                JobListingScope::TalentPoolInternal->value,
                JobListingScope::TalentPoolAndPublic->value,
            ])
            ->withCount('applications')
            ->latest()
            ->paginate(12)
            ->withQueryString()
            ->through(fn (Job $job) => $job->toAdminArray());

        return Inertia::render('Account/TalentPool/Jobs/Index', [
            'jobs' => $jobs,
            'listing_scope_options' => JobListingScope::talentPoolCreateOptions(),
        ]);
    }

    public function create(Request $request, MasterDataService $masterData, EmployerPostingEligibilityService $eligibility): Response
    {
        abort(403, 'Create Job for Talent Pool is available in Admin → Talent Pool only.');
        $this->authorize('create', Job::class);
        $profile = $this->profile($request);

        return Inertia::render('Account/TalentPool/Jobs/Form', [
            'job' => null,
            'company_name' => $profile->displayCompany(),
            'lookups' => $masterData->formOptions(),
            'listing_scope_options' => JobListingScope::talentPoolCreateOptions(),
            'eligibility' => $eligibility->for($request->user()),
        ]);
    }

    public function store(
        StoreJobRequest $request,
        EmployerPostingEligibilityService $eligibility,
        EmployerJobPublicationPolicy $publicationPolicy,
        JobLifecycleService $lifecycle,
        JobListingRequirementService $requirements,
    ): RedirectResponse {
        abort(403, 'Create Job for Talent Pool is available in Admin → Talent Pool only.');
        $this->authorize('create', Job::class);
        $profile = $this->profile($request);
        $user = $request->user();
        abort_unless($user !== null, 403);

        $scopeValue = (string) $request->input('listing_scope', JobListingScope::TalentPoolInternal->value);
        $scope = JobListingScope::tryFrom($scopeValue) ?? JobListingScope::TalentPoolInternal;
        if (! in_array($scope, [JobListingScope::TalentPoolInternal, JobListingScope::TalentPoolAndPublic], true)) {
            throw ValidationException::withMessages([
                'listing_scope' => 'Select a valid Talent Pool publication destination.',
            ]);
        }

        $validated = $request->validated();
        $validated['listing_scope'] = $scope->value;
        $validated['publishing_status'] = JobPublishingStatus::Draft->value;

        if ($scope === JobListingScope::TalentPoolAndPublic && $request->boolean('publish_now')) {
            if (! $eligibility->allows($user)) {
                throw ValidationException::withMessages([
                    'posting' => $eligibility->for($user)['reason'],
                ]);
            }
            $validated['publishing_status'] = JobPublishingStatus::Published->value;
        }

        $job = DB::transaction(function () use ($request, $validated, $profile, $publicationPolicy, $lifecycle, $requirements, $scope) {
            $data = $validated;
            $data['employer_id'] = $profile->id;
            $data['active'] = ($data['publishing_status'] ?? '') === JobPublishingStatus::Published->value;

            if ($data['active']) {
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

        return redirect()
            ->route('account.talent-pool.jobs.index')
            ->with('success', $scope === JobListingScope::TalentPoolInternal
                ? 'Talent Pool job saved as internal recruitment listing (not public).'
                : 'Talent Pool job saved with selected publication destination.');
    }

    private function profile(Request $request): EmployerProfile
    {
        $user = $request->user();
        abort_unless($user !== null, 403);
        $profile = $user->employerProfile;
        abort_unless($profile !== null, 403);

        return $profile;
    }
}
