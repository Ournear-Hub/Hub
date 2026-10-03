<?php

namespace App\Services;

use App\Enums\ApplicationStatus;
use App\Enums\FileLifecycleStatus;
use App\Enums\RecruitmentActivityType;
use App\Models\Application;
use App\Models\CandidateResume;
use App\Models\Job;
use App\Models\RecruitmentActivity;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CandidateApplicationViewService
{
    /**
     * @return LengthAwarePaginator<int, Application>
     */
    public function paginate(User $user, Request $request): LengthAwarePaginator
    {
        $query = $user->applications()
            ->with('job')
            ->latest();

        $filter = trim((string) $request->query('filter', 'all'));
        $statuses = ApplicationStatus::candidateFilterStatuses($filter);
        if ($statuses !== []) {
            $query->whereIn('status', array_map(fn (ApplicationStatus $status) => $status->value, $statuses));
        }

        return $query->paginate(10)->withQueryString();
    }

    /**
     * @return array<string, mixed>
     */
    public function listItem(Application $application): array
    {
        $this->loadListRelations($application);
        $timeline = $this->timeline($application);
        $latest = $timeline[array_key_last($timeline)] ?? null;

        return [
            'id' => $application->id,
            'business_id' => $application->business_id,
            'applied_at' => $application->created_at?->toDateTimeString(),
            'updated_at' => $application->updated_at?->toDateTimeString(),
            'last_updated_at' => is_array($latest) ? ($latest['at'] ?? $application->updated_at?->toDateTimeString()) : $application->updated_at?->toDateTimeString(),
            'status' => $application->status->toCandidateStatusLabel(),
            'has_resume' => $this->hasResume($application),
            'resume_url' => route('account.applications.resume', $application, false),
            'can_withdraw' => $application->candidateCanWithdraw(),
            'withdraw_requires_confirmation' => $application->status === ApplicationStatus::Selected,
            'href' => route('account.applications.show', $application, false),
            'job' => $this->jobProjection($application->job),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function detail(Application $application): array
    {
        $this->loadDetailRelations($application);
        $timeline = $this->timeline($application);
        $latest = $timeline[array_key_last($timeline)] ?? null;

        return [
            'id' => $application->id,
            'business_id' => $application->business_id,
            'applied_at' => $application->created_at?->toDateTimeString(),
            'updated_at' => $application->updated_at?->toDateTimeString(),
            'last_updated_at' => is_array($latest) ? ($latest['at'] ?? $application->updated_at?->toDateTimeString()) : $application->updated_at?->toDateTimeString(),
            'status' => $application->status->toCandidateStatusLabel(),
            'can_withdraw' => $application->candidateCanWithdraw(),
            'withdraw_requires_confirmation' => $application->status === ApplicationStatus::Selected,
            'job' => $this->jobProjection($application->job),
            'resume' => $this->resumeProjection($application),
            'timeline' => $timeline,
        ];
    }

    /**
     * @return list<array{id: int, title: string, is_primary: bool, extension: string|null}>
     */
    public function libraryOptions(?User $user): array
    {
        if (! $user instanceof User || ! $user->isCandidate()) {
            return [];
        }

        $profile = $user->candidateProfile;
        if ($profile === null) {
            $user->loadMissing('candidateProfile');
            $profile = $user->candidateProfile;
        }

        if ($profile === null) {
            return [];
        }

        return $profile->resumes()
            ->where('status', FileLifecycleStatus::Active)
            ->orderByDesc('is_primary')
            ->orderByDesc('id')
            ->get()
            ->map(fn (CandidateResume $resume) => [
                'id' => $resume->id,
                'title' => $resume->title,
                'is_primary' => (bool) $resume->is_primary,
                'extension' => $resume->extension,
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array{key: string, label: string, at: string|null}>
     */
    public function timeline(Application $application): array
    {
        $events = [[
            'key' => 'submitted',
            'label' => 'Application submitted',
            'at' => $application->created_at?->toDateTimeString(),
        ]];

        $seen = ['submitted' => true];

        foreach ($application->activities as $activity) {
            $mapped = $this->mapActivity($activity);
            if ($mapped === null || isset($seen[$mapped['key']])) {
                continue;
            }

            $seen[$mapped['key']] = true;
            $events[] = $mapped;
        }

        return $events;
    }

    /**
     * @return array{key: string, label: string, at: string|null}|null
     */
    private function mapActivity(RecruitmentActivity $activity): ?array
    {
        $key = match ($activity->type) {
            RecruitmentActivityType::StatusChanged => $this->statusChangeKey($activity->to_status),
            RecruitmentActivityType::Shortlisted => 'shortlisted',
            RecruitmentActivityType::CandidateSubmitted => 'submitted_to_employer',
            RecruitmentActivityType::InterviewScheduled => 'interview',
            RecruitmentActivityType::EmployerSelected => 'selected',
            RecruitmentActivityType::PlacementCreated => 'placement',
            RecruitmentActivityType::JoiningConfirmed, RecruitmentActivityType::CandidateJoined => 'joining',
            default => null,
        };

        if ($key === null) {
            return null;
        }

        return [
            'key' => $key,
            'label' => $this->timelineLabel($key),
            'at' => $activity->created_at?->toDateTimeString(),
        ];
    }

    private function statusChangeKey(?string $status): ?string
    {
        return match (ApplicationStatus::tryFrom((string) $status)) {
            ApplicationStatus::Reviewing => 'under_review',
            ApplicationStatus::Shortlisted => 'shortlisted',
            ApplicationStatus::Interview => 'interview',
            ApplicationStatus::Selected => 'selected',
            ApplicationStatus::Placed => 'placement',
            ApplicationStatus::Rejected => 'rejected',
            ApplicationStatus::Withdrawn => 'withdrawn',
            default => null,
        };
    }

    private function timelineLabel(string $key): string
    {
        return match ($key) {
            'submitted' => 'Application submitted',
            'under_review' => 'Application under review',
            'shortlisted' => 'Shortlisted',
            'submitted_to_employer' => 'Submitted to employer',
            'interview' => 'Interview scheduled',
            'selected' => 'Selected',
            'placement' => 'Placement',
            'joining' => 'Joining',
            'rejected' => 'Rejected',
            'withdrawn' => 'Withdrawn',
            default => 'Update',
        };
    }

    /**
     * @return array<string, mixed>|null
     */
    private function jobProjection(?Job $job): ?array
    {
        if (! $job instanceof Job) {
            return null;
        }

        $closed = $job->isClosed();
        $canView = $job->isPubliclyVisible();

        return [
            'title' => $job->title,
            'slug' => $job->slug,
            'company' => $job->project,
            'location' => $job->location,
            'employment_type' => $job->employment_type,
            'experience' => $job->experience,
            'salary' => $job->salary,
            'is_closed' => $closed,
            'status_label' => $closed ? 'Closed' : $job->closingLabel(),
            'can_view' => $canView,
            'href' => $canView ? route('jobs.show', $job, false) : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function resumeProjection(Application $application): array
    {
        $path = $application->getAttributes()['resume_path'] ?? null;
        $exists = is_string($path) && $path !== '' && Storage::disk('resumes')->exists($path);
        $extension = is_string($path) && $path !== '' ? strtolower((string) pathinfo($path, PATHINFO_EXTENSION)) : null;

        return [
            'has_resume' => $exists,
            'filename' => $exists ? 'Application resume'.($extension ? '.'.$extension : '') : null,
            'extension' => $extension,
            'size' => $exists ? Storage::disk('resumes')->size($path) : null,
            'download_url' => $exists ? route('account.applications.resume', $application, false) : null,
        ];
    }

    private function hasResume(Application $application): bool
    {
        return filled($application->getRawOriginal('resume_path') ?? $application->getAttributes()['resume_path'] ?? null);
    }

    private function loadListRelations(Application $application): void
    {
        $application->loadMissing(['job', 'activities']);
    }

    private function loadDetailRelations(Application $application): void
    {
        $application->loadMissing(['job', 'activities']);
    }
}
