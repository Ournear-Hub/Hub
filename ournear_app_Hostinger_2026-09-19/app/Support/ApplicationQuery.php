<?php

namespace App\Support;

use App\Enums\ApplicationStatus;
use App\Enums\ScreeningDecision;
use App\Enums\ScreeningStage;
use App\Models\Application;
use App\Models\CandidateProfile;
use App\Models\EmployerProfile;
use App\Models\ExperienceRange;
use App\Models\Job;
use App\Models\Location;
use App\Models\Qualification;
use App\Services\ScreeningMatchService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;

class ApplicationQuery
{
    /**
     * @return LengthAwarePaginator<int, Application>
     */
    public function adminIndex(Request $request, string $workspace = 'applications'): LengthAwarePaginator
    {
        $query = Application::query()
            ->with([
                'job.classifiedIndustry',
                'job.classifiedLocation',
                'job.classifiedExperienceRange',
                'job.employer.user',
                'user.candidateProfile',
                'screening.screener:id,name',
                'screening.shortlister:id,name',
                'screening.holder:id,name',
                'submissions',
                'activities.actor:id,name',
                'classifiedLocation',
                'classifiedExperienceRange',
                'classifiedQualification',
            ])
            ->latest();

        if ($workspace === 'shortlisted') {
            $this->constrainShortlisted($query);
        }

        $this->applySearch($query, $request);
        $this->applyFilters($query, $request, $workspace);

        if ($this->hasMatchRange($request)) {
            return $this->paginateByMatch($query, $request);
        }

        return $query->paginate(15)->withQueryString();
    }

    public function selected(Request $request): ?Application
    {
        $id = $request->integer('application');
        if ($id <= 0) {
            return null;
        }

        return Application::query()
            ->with([
                'job.classifiedIndustry',
                'job.classifiedCategory',
                'job.classifiedLocation',
                'job.classifiedEmploymentType',
                'job.classifiedExperienceRange',
                'job.employer.user',
                'user.candidateProfile.classifiedLocation',
                'user.candidateProfile.classifiedExperienceRange',
                'user.candidateProfile.classifiedQualification',
                'screening.rejectionReason',
                'screening.screener:id,name',
                'screening.shortlister:id,name',
                'screening.holder:id,name',
                'interview',
                'activities.actor:id,name',
                'submissions.employer.user',
                'submissions.placement',
                'placements.employee',
                'classifiedLocation',
                'classifiedExperienceRange',
                'classifiedQualification',
            ])
            ->find($id);
    }

    /**
     * @return array<string, mixed>
     */
    public function adminFilterOptions(): array
    {
        return [
            'jobs' => Job::query()->orderBy('title')->get(['id', 'title', 'slug', 'business_id'])->map(fn (Job $job) => [
                'id' => $job->id,
                'title' => $job->title,
                'slug' => $job->slug,
                'business_id' => $job->business_id,
            ])->all(),
            'locations' => Location::query()->ordered()->get(['id', 'name'])->map(fn (Location $location) => [
                'id' => $location->id,
                'name' => $location->name,
            ])->all(),
            'experiences' => ExperienceRange::query()->ordered()->get(['id', 'name'])->map(fn (ExperienceRange $range) => [
                'id' => $range->id,
                'name' => $range->name,
            ])->all(),
            'qualifications' => Qualification::query()->ordered()->get(['id', 'name'])->map(fn (Qualification $qualification) => [
                'id' => $qualification->id,
                'name' => $qualification->name,
            ])->all(),
            'screening_decisions' => array_map(
                fn (ScreeningDecision $decision) => [
                    'value' => $decision->value,
                    'label' => $decision->label(),
                ],
                ScreeningDecision::cases(),
            ),
            'screening_stages' => ScreeningStage::options(),
            'employers' => EmployerProfile::query()
                ->with('user')
                ->latest()
                ->get()
                ->map(fn (EmployerProfile $employer) => $employer->toSelectArray())
                ->values()
                ->all(),
            'candidates' => CandidateProfile::query()
                ->with('user')
                ->whereHas('applications')
                ->latest()
                ->get()
                ->map(fn (CandidateProfile $profile) => [
                    'id' => $profile->id,
                    'business_id' => $profile->business_id,
                    'name' => $profile->user?->name,
                    'label' => trim(($profile->business_id ? $profile->business_id.' · ' : '').($profile->user?->name ?: 'Candidate')),
                ])
                ->values()
                ->all(),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function filters(Request $request): array
    {
        $selectedId = $request->integer('application');

        return [
            'search' => $request->string('search')->toString(),
            'status' => $request->string('status')->toString(),
            'job_id' => $request->string('job_id')->toString(),
            'location_id' => $request->string('location_id')->toString(),
            'experience_range_id' => $request->string('experience_range_id')->toString(),
            'qualification_id' => $request->string('qualification_id')->toString(),
            'location' => $request->string('location')->toString(),
            'experience' => $request->string('experience')->toString(),
            'qualification' => $request->string('qualification')->toString(),
            'industry' => $request->string('industry')->toString(),
            'date_from' => $request->string('date_from')->toString(),
            'date_to' => $request->string('date_to')->toString(),
            'shortlisted_from' => $request->string('shortlisted_from')->toString(),
            'shortlisted_to' => $request->string('shortlisted_to')->toString(),
            'screening_decision' => $request->string('screening_decision')->toString(),
            'screening_stage' => $request->string('screening_stage')->toString(),
            'match_range' => $request->string('match_range')->toString(),
            'verification_state' => $request->string('verification_state')->toString(),
            'candidate' => $request->string('candidate')->toString(),
            'employer_id' => $request->string('employer_id')->toString(),
            'application' => $selectedId > 0 ? (string) $selectedId : '',
        ];
    }

    /**
     * @param  Builder<Application>  $query
     */
    private function applySearch(Builder $query, Request $request): void
    {
        $search = trim((string) $request->query('search', ''));
        if ($search === '') {
            return;
        }

        $query->where(function (Builder $inner) use ($search) {
            $inner->where('business_id', 'like', '%'.$search.'%')
                ->orWhere('full_name', 'like', '%'.$search.'%')
                ->orWhere('email', 'like', '%'.$search.'%')
                ->orWhere('mobile', 'like', '%'.$search.'%')
                ->orWhereHas('job', function (Builder $job) use ($search) {
                    $job->where('business_id', 'like', '%'.$search.'%')
                        ->orWhere('title', 'like', '%'.$search.'%');
                })
                ->orWhereHas('user.candidateProfile', function (Builder $profile) use ($search) {
                    $profile->where('business_id', 'like', '%'.$search.'%');
                });
        });
    }

    /**
     * @param  Builder<Application>  $query
     */
    private function applyFilters(Builder $query, Request $request, string $workspace): void
    {
        $status = trim((string) $request->query('status', ''));
        if ($status !== '') {
            $query->where('status', $status);
        }

        $jobId = $request->query('job_id');
        if (filled($jobId)) {
            $query->where('job_id', (int) $jobId);
        }

        $employerId = $request->integer('employer_id');
        if ($employerId > 0) {
            $query->whereHas('job', fn (Builder $job) => $job->where('employer_id', $employerId));
        }

        $industry = trim((string) $request->query('industry', ''));
        if ($industry !== '') {
            $query->whereHas('job', fn (Builder $job) => $job->where('industry', $industry));
        }

        $this->applyMasterOrLegacyFilter($query, $request, 'location_id', 'current_location', Location::class);
        $this->applyMasterOrLegacyFilter($query, $request, 'experience_range_id', 'experience', ExperienceRange::class);
        $this->applyMasterOrLegacyFilter($query, $request, 'qualification_id', 'qualification', Qualification::class);

        $location = trim((string) $request->query('location', ''));
        if ($location !== '') {
            $query->where(function (Builder $inner) use ($location) {
                $inner->where('current_location', $location)
                    ->orWhereHas('classifiedLocation', fn (Builder $master) => $master->where('name', $location));
            });
        }

        $experience = trim((string) $request->query('experience', ''));
        if ($experience !== '') {
            $query->where(function (Builder $inner) use ($experience) {
                $inner->where('experience', $experience)
                    ->orWhereHas('classifiedExperienceRange', fn (Builder $master) => $master->where('name', $experience));
            });
        }

        $qualification = trim((string) $request->query('qualification', ''));
        if ($qualification !== '') {
            $query->where(function (Builder $inner) use ($qualification) {
                $inner->where('qualification', $qualification)
                    ->orWhereHas('classifiedQualification', fn (Builder $master) => $master->where('name', $qualification));
            });
        }

        $from = trim((string) $request->query('date_from', ''));
        if ($from !== '') {
            $query->whereDate('created_at', '>=', $from);
        }

        $to = trim((string) $request->query('date_to', ''));
        if ($to !== '') {
            $query->whereDate('created_at', '<=', $to);
        }

        $decision = trim((string) $request->query('screening_decision', ''));
        if ($decision !== '') {
            $this->constrainScreeningDecision($query, $decision);
        }

        $stage = trim((string) $request->query('screening_stage', ''));
        if ($stage !== '') {
            $this->constrainScreeningStage($query, $stage);
        }

        $verification = trim((string) $request->query('verification_state', ''));
        if ($verification === 'verified') {
            $query->whereHas('user', fn (Builder $user) => $user->whereNotNull('email_verified_at'));
        } elseif ($verification === 'unverified') {
            $query->where(function (Builder $inner) {
                $inner->whereNull('user_id')
                    ->orWhereHas('user', fn (Builder $user) => $user->whereNull('email_verified_at'));
            });
        }

        $candidate = $request->integer('candidate');
        if ($candidate > 0) {
            $userId = CandidateProfile::query()->whereKey($candidate)->value('user_id');
            $query->where('user_id', $userId ?: 0);
        }

        if ($workspace === 'shortlisted') {
            $shortlistedFrom = trim((string) $request->query('shortlisted_from', ''));
            if ($shortlistedFrom !== '') {
                $query->whereHas('screening', fn (Builder $screening) => $screening->whereDate('shortlisted_at', '>=', $shortlistedFrom));
            }

            $shortlistedTo = trim((string) $request->query('shortlisted_to', ''));
            if ($shortlistedTo !== '') {
                $query->whereHas('screening', fn (Builder $screening) => $screening->whereDate('shortlisted_at', '<=', $shortlistedTo));
            }
        }
    }

    /**
     * @param  Builder<Application>  $query
     */
    private function constrainShortlisted(Builder $query): void
    {
        $query->where(function (Builder $inner) {
            $inner->where('status', ApplicationStatus::Shortlisted->value)
                ->orWhereHas('screening', fn (Builder $screening) => $screening->where('decision', ScreeningDecision::Shortlisted->value));
        });
    }

    /**
     * @param  Builder<Application>  $query
     */
    private function constrainScreeningDecision(Builder $query, string $decision): void
    {
        if ($decision === ScreeningDecision::Pending->value) {
            $query->where(function (Builder $inner) {
                $inner->whereDoesntHave('screening')
                    ->orWhereHas('screening', fn (Builder $screening) => $screening->where('decision', ScreeningDecision::Pending->value));
            });

            return;
        }

        $query->whereHas('screening', fn (Builder $screening) => $screening->where('decision', $decision));
    }

    /**
     * @param  Builder<Application>  $query
     */
    private function constrainScreeningStage(Builder $query, string $stage): void
    {
        if ($stage === ScreeningStage::Pending->value) {
            $query->where(function (Builder $inner) {
                $inner->whereDoesntHave('screening')
                    ->orWhereHas('screening', fn (Builder $screening) => $screening->where('stage', ScreeningStage::Pending->value));
            });

            return;
        }

        $query->whereHas('screening', fn (Builder $screening) => $screening->where('stage', $stage));
    }

    private function hasMatchRange(Request $request): bool
    {
        $range = trim((string) $request->query('match_range', ''));

        return in_array($range, ['0-49', '50-74', '75-100'], true);
    }

    /**
     * @param  Builder<Application>  $query
     * @return LengthAwarePaginator<int, Application>
     */
    private function paginateByMatch(Builder $query, Request $request): LengthAwarePaginator
    {
        [$min, $max] = match (trim((string) $request->query('match_range', ''))) {
            '0-49' => [0, 49],
            '50-74' => [50, 74],
            default => [75, 100],
        };

        $match = app(ScreeningMatchService::class);
        $filtered = $query->get()->filter(function (Application $application) use ($match, $min, $max) {
            $result = $match->evaluate($application);

            if ($result['state'] !== 'scored' || $result['percent'] === null) {
                return false;
            }

            return $result['percent'] >= $min && $result['percent'] <= $max;
        })->values();

        $page = max(1, (int) $request->query('page', 1));
        $perPage = 15;
        $slice = $filtered->forPage($page, $perPage)->values();

        return new Paginator($slice, $filtered->count(), $perPage, $page, [
            'path' => $request->url(),
            'query' => $request->query(),
        ]);
    }

    /**
     * @param  Builder<Application>  $query
     * @param  class-string  $model
     */
    private function applyMasterOrLegacyFilter(Builder $query, Request $request, string $idColumn, string $legacyColumn, string $model): void
    {
        $id = $request->integer($idColumn);
        if ($id <= 0) {
            return;
        }

        $name = $model::query()->whereKey($id)->value('name');

        $query->where(function (Builder $inner) use ($id, $idColumn, $legacyColumn, $name) {
            $inner->where($idColumn, $id);

            if (is_string($name) && $name !== '') {
                $inner->orWhere(function (Builder $legacy) use ($idColumn, $legacyColumn, $name) {
                    $legacy->whereNull($idColumn)->where($legacyColumn, $name);
                });
            }
        });
    }
}
