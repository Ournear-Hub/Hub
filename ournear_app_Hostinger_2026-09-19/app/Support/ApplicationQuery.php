<?php

namespace App\Support;

use App\Enums\ApplicationStatus;
use App\Enums\ScreeningDecision;
use App\Models\Application;
use App\Models\CandidateProfile;
use App\Models\ExperienceRange;
use App\Models\Job;
use App\Models\Location;
use App\Models\Qualification;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

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
                'user.candidateProfile',
                'screening',
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
                'user.candidateProfile.classifiedLocation',
                'user.candidateProfile.classifiedExperienceRange',
                'user.candidateProfile.classifiedQualification',
                'screening.rejectionReason',
                'screening.screener:id,name',
                'screening.shortlister:id,name',
                'interview',
                'activities.actor:id,name',
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
            'candidate' => $request->string('candidate')->toString(),
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
