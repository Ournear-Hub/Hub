<?php

namespace App\Http\Controllers\Admin;

use App\Enums\JobListingScope;
use App\Enums\JobPublishingStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreJobRequest;
use App\Models\EmployerProfile;
use App\Models\EmploymentType;
use App\Models\ExperienceRange;
use App\Models\Industry;
use App\Models\Job;
use App\Models\JobCategory;
use App\Models\Location;
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

/**
 * Create Job for Talent Pool — Admin → Talent Pool only (not employer ATS, not account recruiter workspace).
 */
class TalentPoolJobsController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Job::class);

        $employerId = $request->integer('employer_id');

        $query = Job::query()
            ->whereIn('listing_scope', [
                JobListingScope::TalentPoolInternal->value,
                JobListingScope::TalentPoolAndPublic->value,
            ])
            ->with('employer')
            ->withCount('applications')
            ->latest();

        if ($employerId > 0) {
            $query->where('employer_id', $employerId);
        }

        $jobs = $query
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Job $job) => $job->toAdminArray());

        return Inertia::render('Admin/TalentPoolJobs/Index', [
            'jobs' => $jobs,
            'employers' => $this->employerOptions(),
            'filters' => ['employer_id' => $employerId > 0 ? $employerId : ''],
            'listing_scope_options' => JobListingScope::talentPoolCreateOptions(),
        ]);
    }

    public function create(Request $request, MasterDataService $masterData): Response
    {
        $this->authorize('create', Job::class);

        $employerId = $request->integer('employer_id');
        $profile = $employerId > 0 ? EmployerProfile::query()->find($employerId) : null;

        return Inertia::render('Admin/TalentPoolJobs/Form', [
            'job' => null,
            'employer_profile_id' => $profile?->id,
            'company_name' => $profile?->displayCompany(),
            'employers' => $this->employerOptions(),
            'lookups' => [
                ...$masterData->formOptions(),
                'employers' => EmployerProfile::query()
                    ->with('user')
                    ->orderBy('company_name')
                    ->get()
                    ->map(fn (EmployerProfile $employer) => $employer->toSelectArray())
                    ->values()
                    ->all(),
            ],
            'listing_scope_options' => JobListingScope::talentPoolCreateOptions(),
        ]);
    }

    public function store(
        StoreJobRequest $request,
        EmployerJobPublicationPolicy $publicationPolicy,
        JobListingRequirementService $requirements,
    ): RedirectResponse {
        $this->authorize('create', Job::class);

        $employerId = (int) ($request->input('employer_id') ?: $request->input('employer_profile_id'));
        if ($employerId <= 0) {
            throw ValidationException::withMessages([
                'employer_id' => 'Select the company that owns this Talent Pool job listing.',
            ]);
        }

        $profile = EmployerProfile::query()->findOrFail($employerId);
        $owner = $profile->user;
        abort_unless($owner !== null, 422);

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
            $eligibility = app(EmployerPostingEligibilityService::class);
            if (! $eligibility->allows($owner)) {
                throw ValidationException::withMessages([
                    'posting' => $eligibility->for($owner)['reason'],
                ]);
            }
            $validated['publishing_status'] = JobPublishingStatus::Published->value;
        }

        DB::transaction(function () use ($request, $validated, $profile, $publicationPolicy, $requirements) {
            $data = $this->buildCreatePayload($request, $validated, $profile, $publicationPolicy);

            $created = Job::query()->create($data);
            $requirements->sync(
                $created,
                $request->input('qualification_ids'),
                $request->input('skill_ids'),
            );
        });

        return redirect()
            ->route('admin.talent-pool-jobs.index', ['employer_id' => $profile->id])
            ->with('success', $scope === JobListingScope::TalentPoolInternal
                ? 'Talent Pool job saved as internal recruitment listing (not public).'
                : 'Talent Pool job saved with selected publication destination.');
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function buildCreatePayload(
        StoreJobRequest $request,
        array $validated,
        EmployerProfile $profile,
        EmployerJobPublicationPolicy $publicationPolicy,
    ): array {
        $data = $validated;
        $data['employer_id'] = $profile->id;
        $data['featured'] = $request->boolean('featured');
        $data['urgent'] = $request->boolean('urgent');
        $data['salary'] = $data['salary'] ?: null;
        $data['joining_date'] = $data['joining_date'] ?? null;
        $data['site'] = ($data['site'] ?? null) ?: null;
        $data['accommodation'] = ($data['accommodation'] ?? null) ?: null;
        $data['responsibilities'] = $data['responsibilities'] ?: null;
        $data['requirements'] = $data['requirements'] ?: null;
        $data['what_we_offer'] = $data['what_we_offer'] ?: null;

        $status = $data['publishing_status'] ?? JobPublishingStatus::Draft->value;
        $enum = $status instanceof JobPublishingStatus
            ? $status
            : JobPublishingStatus::tryFrom((string) $status);
        if ($enum instanceof JobPublishingStatus) {
            $data['active'] = app(JobLifecycleService::class)->syncActiveForStatus(
                $enum,
                $request->boolean('active'),
            );
        } else {
            $data['active'] = $request->boolean('active');
        }

        $this->syncMasterString($data, 'industry_id', 'industry', Industry::class);
        $this->syncMasterString($data, 'job_category_id', 'category', JobCategory::class);
        $this->syncMasterString($data, 'location_id', 'location', Location::class);
        $this->syncMasterString($data, 'employment_type_id', 'employment_type', EmploymentType::class);
        $this->syncMasterString($data, 'experience_range_id', 'experience', ExperienceRange::class);

        if (! filled($data['job_title_id'] ?? null)) {
            $data['job_title_id'] = null;
        }

        $data['slug'] = Job::uniqueSlug($data['title']);

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

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  class-string  $model
     */
    private function syncMasterString(array &$data, string $idKey, string $stringKey, string $model): void
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
    }

    /**
     * @return list<array{id: int, name: string}>
     */
    private function employerOptions(): array
    {
        return EmployerProfile::query()
            ->whereNotNull('company_name')
            ->where('company_name', '!=', '')
            ->orderBy('company_name')
            ->get(['id', 'company_name'])
            ->map(fn (EmployerProfile $employer) => [
                'id' => $employer->id,
                'name' => $employer->displayCompany(),
            ])
            ->values()
            ->all();
    }
}
