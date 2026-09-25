<?php

namespace App\Services;

use App\Enums\FileLifecycleStatus;
use App\Models\Application;
use App\Models\CandidateResume;
use App\Models\Job;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class PublicJobStateService
{
    public function __construct(private SavedJobService $savedJobs) {}

    /**
     * @return array<string, mixed>
     */
    public function decorate(Job $job, ?User $user, bool $detail = false): array
    {
        $payload = $detail ? $job->toPublicDetail() : $job->toPublicCard();

        return [
            ...$payload,
            ...$this->stateFor($job, $user, $this->savedJobs->isSaved($user, $job), $this->hasApplied($user, $job), $this->hasUsableResume($user)),
        ];
    }

    /**
     * @param  Collection<int, Job>  $jobs
     * @return list<array<string, mixed>>
     */
    public function decorateCollection(Collection $jobs, ?User $user, bool $detail = false): array
    {
        $ids = $jobs->map(fn (Job $job) => (int) $job->id)->all();
        $saved = $user instanceof User ? $this->savedJobs->idSetFor($user, $ids) : [];
        $applied = $this->appliedJobIds($user, $ids);
        $hasResume = $this->hasUsableResume($user);

        return $jobs->map(function (Job $job) use ($user, $detail, $saved, $applied, $hasResume) {
            $payload = $detail ? $job->toPublicDetail() : $job->toPublicCard();

            return [
                ...$payload,
                ...$this->stateFor(
                    $job,
                    $user,
                    isset($saved[(int) $job->id]),
                    isset($applied[(int) $job->id]),
                    $hasResume,
                ),
            ];
        })->values()->all();
    }

    public function hasUsableResume(?User $user): bool
    {
        if (! $user instanceof User || ! $user->isCandidate()) {
            return false;
        }

        $user->loadMissing('candidateProfile');
        $profile = $user->candidateProfile;
        if ($profile === null) {
            return false;
        }

        return CandidateResume::query()
            ->where('candidate_profile_id', $profile->id)
            ->where('status', FileLifecycleStatus::Active)
            ->exists();
    }

    public function primaryResumeId(?User $user): ?int
    {
        if (! $user instanceof User || ! $user->isCandidate()) {
            return null;
        }

        $user->loadMissing('candidateProfile');
        $profile = $user->candidateProfile;
        if ($profile === null) {
            return null;
        }

        $resume = CandidateResume::query()
            ->where('candidate_profile_id', $profile->id)
            ->where('status', FileLifecycleStatus::Active)
            ->orderByDesc('is_primary')
            ->orderByDesc('id')
            ->first();

        return $resume?->id;
    }

    /**
     * @return array<string, mixed>
     */
    private function stateFor(Job $job, ?User $user, bool $isSaved, bool $hasApplied, bool $hasResume): array
    {
        $applyState = 'guest';

        if ($user?->isEmployer() || $user?->isAdmin()) {
            $applyState = 'hidden';
        } elseif (! $job->acceptsApplications()) {
            $applyState = 'closed';
        } elseif (! $user instanceof User) {
            $applyState = 'guest';
        } elseif ($user->isCandidate()) {
            $applyState = $hasApplied ? 'applied' : ($hasResume ? 'apply' : 'upload_resume');
        }

        return [
            'is_saved' => $user?->isCandidate() ? $isSaved : ($user instanceof User ? null : false),
            'has_applied' => $hasApplied,
            'has_resume' => $hasResume,
            'apply_state' => $applyState,
        ];
    }

    private function hasApplied(?User $user, Job $job): bool
    {
        if (! $user instanceof User || ! $user->isCandidate()) {
            return false;
        }

        return Application::query()
            ->where('job_id', $job->id)
            ->where(function ($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->orWhereRaw('LOWER(email) = ?', [Str::lower($user->email)]);
            })
            ->exists();
    }

    /**
     * @param  list<int>  $jobIds
     * @return array<int, true>
     */
    private function appliedJobIds(?User $user, array $jobIds): array
    {
        if (! $user instanceof User || ! $user->isCandidate() || $jobIds === []) {
            return [];
        }

        return Application::query()
            ->whereIn('job_id', $jobIds)
            ->where(function ($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->orWhereRaw('LOWER(email) = ?', [Str::lower($user->email)]);
            })
            ->pluck('job_id')
            ->mapWithKeys(fn ($id) => [(int) $id => true])
            ->all();
    }
}
