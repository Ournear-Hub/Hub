<?php

namespace App\Support;

use App\Enums\ApplicationStatus;
use App\Enums\EmployerSubmissionStatus;
use App\Enums\PlacementStatus;
use App\Enums\ScreeningDecision;
use App\Enums\ScreeningStage;
use App\Support\AtsStage;
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
            'ats_stages' => AtsStage::options(),
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
            'match_min' => $request->string('match_min')->toString(),
            'match_max' => $request->string('match_max')->toString(),
            'match_sort' => $request->string('match_sort')->toString(),
            'verification_state' => $request->string('verification_state')->toString(),
            'candidate' => $request->string('candidate')->toString(),
            'employer_id' => $request->string('employer_id')->toString(),
            'ats_stage' => $request->string('ats_stage')->toString(),
            'scope' => $request->string('scope')->toString(),
            'application' => $selectedId > 0 ? (string) $selectedId : '',
        ];
    }

    /**
     * Applications that still need a human screening decision.
     *
     * @return Builder<Application>
     */
    public function pendingScreeningQuery(): Builder
    {
        $query = Application::query()
            ->with([
                'job:id,title,business_id',
                'user.candidateProfile',
            ])
            ->latest();

        $this->constrainPendingScreening($query);

        return $query;
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

        $this->applyAtsStageFilter($query, trim((string) $request->query('ats_stage', '')));

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

        $scope = trim((string) $request->query('scope', ''));
        if ($scope === 'pending' && $decision === '') {
            $this->constrainPendingScreening($query);
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
    public function constrainPendingScreening(Builder $query): void
    {
        $this->constrainScreeningDecision($query, ScreeningDecision::Pending->value);
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

        if (in_array($range, ['0-49', '50-74', '75-100', '90-100', '80-89', '70-79', '60-69', 'below-60', 'incomplete'], true)) {
            return true;
        }

        return filled($request->query('match_min'))
            || filled($request->query('match_max'))
            || in_array(trim((string) $request->query('match_sort', '')), ['match_desc', 'match_asc'], true);
    }

    /**
     * @param  Builder<Application>  $query
     * @return LengthAwarePaginator<int, Application>
     */
    private function paginateByMatch(Builder $query, Request $request): LengthAwarePaginator
    {
        $range = trim((string) $request->query('match_range', ''));
        $bounds = match ($range) {
            '0-49' => [0, 49],
            '50-74' => [50, 74],
            '75-100' => [75, 100],
            '90-100' => [90, 100],
            '80-89' => [80, 89],
            '70-79' => [70, 79],
            '60-69' => [60, 69],
            'below-60' => [0, 59],
            default => [null, null],
        };

        $min = $request->filled('match_min') ? (int) $request->query('match_min') : $bounds[0];
        $max = $request->filled('match_max') ? (int) $request->query('match_max') : $bounds[1];
        $incompleteOnly = $range === 'incomplete';

        $match = app(ScreeningMatchService::class);
        $scored = $query->get()->map(function (Application $application) use ($match) {
            return ['application' => $application, 'result' => $match->evaluate($application)];
        });

        $filtered = $scored->filter(function (array $row) use ($incompleteOnly, $min, $max) {
            $result = $row['result'];
            $incomplete = $result['state'] !== 'scored' || $result['percent'] === null;

            if ($incompleteOnly) {
                return $incomplete;
            }

            if ($incomplete) {
                return false;
            }

            $percent = (int) $result['percent'];

            if ($min !== null && $percent < $min) {
                return false;
            }

            if ($max !== null && $percent > $max) {
                return false;
            }

            return true;
        })->values();

        $sort = trim((string) $request->query('match_sort', ''));
        if ($sort === 'match_asc' || $sort === 'match_desc') {
            $filtered = $filtered->sortBy(
                fn (array $row) => $row['result']['percent'] ?? -1,
                SORT_NUMERIC,
                $sort === 'match_desc',
            )->values();
        }

        $filtered = $filtered->map(fn (array $row) => $row['application'])->values();

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
    /**
     * @param  Builder<Application>  $query
     */
    private function applyAtsStageFilter(Builder $query, string $stage): void
    {
        if ($stage === '') {
            return;
        }

        $joinedPlacement = PlacementStatus::Joined->value;
        $offerPlacements = [
            PlacementStatus::Offered->value,
            PlacementStatus::Accepted->value,
            PlacementStatus::JoiningConfirmed->value,
        ];
        $reviewStatuses = [
            EmployerSubmissionStatus::Submitted->value,
            EmployerSubmissionStatus::UnderReview->value,
        ];
        $closedSubmissions = [
            EmployerSubmissionStatus::OnHold->value,
            EmployerSubmissionStatus::Rejected->value,
        ];

        match ($stage) {
            AtsStage::Joined => $query->where(function (Builder $inner) use ($joinedPlacement) {
                $inner->where('status', ApplicationStatus::Placed)
                    ->orWhereHas('placements', fn (Builder $placement) => $placement->where('status', $joinedPlacement));
            }),
            AtsStage::Offer => $query
                ->where('status', '!=', ApplicationStatus::Placed)
                ->whereDoesntHave('placements', fn (Builder $placement) => $placement->where('status', $joinedPlacement))
                ->whereHas('placements', fn (Builder $placement) => $placement->whereIn('status', $offerPlacements)),
            AtsStage::Selected => $query
                ->where('status', '!=', ApplicationStatus::Placed)
                ->whereDoesntHave('placements', fn (Builder $placement) => $placement->whereIn('status', array_merge($offerPlacements, [$joinedPlacement])))
                ->where(function (Builder $inner) {
                    $inner->where('status', ApplicationStatus::Selected)
                        ->orWhereHas('submissions', fn (Builder $submission) => $submission->where('status', EmployerSubmissionStatus::Selected));
                }),
            AtsStage::Interview => $query
                ->where('status', '!=', ApplicationStatus::Placed)
                ->where('status', '!=', ApplicationStatus::Selected)
                ->whereDoesntHave('placements', fn (Builder $placement) => $placement->whereIn('status', array_merge($offerPlacements, [$joinedPlacement])))
                ->whereDoesntHave('submissions', fn (Builder $submission) => $submission->where('status', EmployerSubmissionStatus::Selected))
                ->where(function (Builder $inner) {
                    $inner->where('status', ApplicationStatus::Interview)
                        ->orWhereHas('interview');
                }),
            AtsStage::EmployerReview => $query
                ->whereNotIn('status', [ApplicationStatus::Placed, ApplicationStatus::Selected, ApplicationStatus::Interview])
                ->whereDoesntHave('interview')
                ->whereDoesntHave('placements', fn (Builder $placement) => $placement->whereIn('status', array_merge($offerPlacements, [$joinedPlacement])))
                ->whereDoesntHave('submissions', fn (Builder $submission) => $submission->where('status', EmployerSubmissionStatus::Selected))
                ->whereHas('submissions', fn (Builder $submission) => $submission->whereIn('status', $reviewStatuses)),
            AtsStage::Shortlisted => $query
                ->whereNotIn('status', [ApplicationStatus::Placed, ApplicationStatus::Selected, ApplicationStatus::Interview])
                ->whereDoesntHave('interview')
                ->whereDoesntHave('submissions', fn (Builder $submission) => $submission->whereIn('status', array_merge($reviewStatuses, [EmployerSubmissionStatus::Selected->value])))
                ->where(function (Builder $inner) {
                    $inner->where('status', ApplicationStatus::Shortlisted)
                        ->orWhereHas('screening', fn (Builder $screening) => $screening
                            ->where('stage', ScreeningStage::Shortlisted)
                            ->orWhere('decision', ScreeningDecision::Shortlisted));
                }),
            AtsStage::Closed => $query
                ->whereNotIn('status', [ApplicationStatus::Placed, ApplicationStatus::Selected, ApplicationStatus::Interview])
                ->whereDoesntHave('interview')
                ->whereDoesntHave('submissions', fn (Builder $submission) => $submission->whereIn('status', array_merge($reviewStatuses, [EmployerSubmissionStatus::Selected->value])))
                ->where(function (Builder $inner) use ($closedSubmissions) {
                    $inner->whereIn('status', [ApplicationStatus::Rejected, ApplicationStatus::Withdrawn])
                        ->orWhereHas('screening', fn (Builder $screening) => $screening->whereIn('stage', [
                            ScreeningStage::Held,
                            ScreeningStage::Rejected,
                        ]))
                        ->orWhereHas('submissions', fn (Builder $submission) => $submission->whereIn('status', $closedSubmissions));
                }),
            AtsStage::Screening => $query
                ->whereNotIn('status', [
                    ApplicationStatus::Placed,
                    ApplicationStatus::Selected,
                    ApplicationStatus::Interview,
                    ApplicationStatus::Shortlisted,
                    ApplicationStatus::Rejected,
                    ApplicationStatus::Withdrawn,
                ])
                ->whereDoesntHave('interview')
                ->whereDoesntHave('submissions')
                ->where(function (Builder $inner) {
                    // Mirrors AtsStage::resolve(): Reviewing status, or an active screening stage.
                    $inner->where('status', ApplicationStatus::Reviewing)
                        ->orWhereHas('screening', fn (Builder $screening) => $screening->whereIn('stage', [
                            ScreeningStage::Started,
                            ScreeningStage::Matched,
                            ScreeningStage::Contacted,
                            ScreeningStage::Called,
                            ScreeningStage::Interviewed,
                        ]));
                })
                ->whereDoesntHave('screening', fn (Builder $screening) => $screening
                    ->whereIn('stage', [ScreeningStage::Held, ScreeningStage::Rejected, ScreeningStage::Shortlisted])
                    ->orWhere('decision', ScreeningDecision::Shortlisted)),
            AtsStage::Applied => $query
                ->where('status', ApplicationStatus::New)
                ->whereDoesntHave('interview')
                ->whereDoesntHave('submissions')
                ->whereDoesntHave('placements')
                ->where(function (Builder $inner) {
                    $inner->whereDoesntHave('screening')
                        ->orWhereHas('screening', fn (Builder $screening) => $screening->where('stage', ScreeningStage::Pending));
                }),
            default => null,
        };
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
