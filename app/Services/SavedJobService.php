<?php

namespace App\Services;

use App\Enums\JobPublishingStatus;
use App\Enums\UserActivityType;
use App\Models\Job;
use App\Models\SavedJob;
use App\Models\User;
use App\Support\JobApplicationEligibility;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Validation\ValidationException;

class SavedJobService
{
    public function __construct(private UserActivityService $activities) {}

    public function save(User $user, Job $job): SavedJob
    {
        $this->assertCandidate($user);

        $existing = SavedJob::query()
            ->where('user_id', $user->id)
            ->where('job_id', $job->id)
            ->first();

        if ($existing) {
            return $existing;
        }

        abort_unless($job->isPubliclyVisible(), 404);

        if (JobApplicationEligibility::candidateOwnsJobCompany($user, $job)) {
            throw ValidationException::withMessages([
                'job' => 'You cannot save a job posted by your own company.',
            ]);
        }

        try {
            $saved = SavedJob::query()->create([
                'user_id' => $user->id,
                'job_id' => $job->id,
            ]);
        } catch (UniqueConstraintViolationException) {
            return SavedJob::query()
                ->where('user_id', $user->id)
                ->where('job_id', $job->id)
                ->firstOrFail();
        }

        $this->activities->record(
            $user,
            UserActivityType::SavedJob,
            'Saved a job.',
            $user,
            [
                'job_slug' => $job->slug,
                'job_title' => $job->title,
            ],
        );

        return $saved;
    }

    public function unsave(User $user, Job $job): void
    {
        $this->assertCandidate($user);

        SavedJob::query()
            ->where('user_id', $user->id)
            ->where('job_id', $job->id)
            ->delete();
    }

    public function isSaved(?User $user, Job $job): bool
    {
        if (! $this->canUseSavedJobs($user)) {
            return false;
        }

        return SavedJob::query()
            ->where('user_id', $user->id)
            ->where('job_id', $job->id)
            ->exists();
    }

    /**
     * @param  list<int|string>  $jobIds
     * @return array<int, true>
     */
    public function idSetFor(?User $user, array $jobIds): array
    {
        if (! $this->canUseSavedJobs($user) || $jobIds === []) {
            return [];
        }

        return SavedJob::query()
            ->where('user_id', $user->id)
            ->whereIn('job_id', $jobIds)
            ->pluck('job_id')
            ->mapWithKeys(fn ($id) => [(int) $id => true])
            ->all();
    }

    /**
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    public function paginate(User $user, string $status = 'all'): LengthAwarePaginator
    {
        $this->assertCandidate($user);

        $query = SavedJob::query()
            ->where('user_id', $user->id)
            ->with([
                'job.employer.user',
                'job.classifiedIndustry',
                'job.classifiedCategory',
                'job.classifiedLocation',
                'job.classifiedEmploymentType',
                'job.classifiedExperienceRange',
            ])
            ->latest('created_at');

        if ($status === 'open') {
            $query->whereHas('job', fn (Builder $job) => $job->publiclyVisible());
        }

        if ($status === 'closed') {
            $query->whereHas('job', function (Builder $job) {
                $job->where(function (Builder $inner) {
                    $inner->where('active', false)
                        ->orWhere('publishing_status', '!=', JobPublishingStatus::Published)
                        ->orWhere(function (Builder $closing) {
                            $closing->whereNotNull('closing_date')
                                ->whereDate('closing_date', '<', now()->toDateString());
                        });
                });
            });
        }

        return $query
            ->paginate(10)
            ->withQueryString()
            ->through(function (SavedJob $saved) use ($user) {
                $job = $saved->job;
                if (! $job instanceof Job) {
                    return $saved->toAccountArray();
                }

                $card = app(PublicJobStateService::class)->decorate($job, $user);

                return [
                    ...$card,
                    'saved_at' => $saved->created_at?->toDateTimeString(),
                    'can_view' => $job->isPubliclyVisible(),
                    'status_label' => $job->isPubliclyVisible()
                        ? 'Open'
                        : ($job->isClosed() ? 'Closed' : 'Unavailable'),
                ];
            });
    }

    private function assertCandidate(User $user): void
    {
        abort_unless($user->isCandidate(), 403);
    }

    private function canUseSavedJobs(?User $user): bool
    {
        return $user instanceof User && $user->isCandidate();
    }
}
