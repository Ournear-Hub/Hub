<?php

namespace App\Support;

use App\Enums\EmployerSubmissionStatus;
use App\Models\EmployerProfile;
use App\Models\EmployerSubmission;
use App\Models\Job;
use App\Models\RecruitmentActivity;
use Illuminate\Database\Eloquent\Builder;

final class EmployerRecruitmentPartnership
{
    public const SUPPORT_CONTACT_HREF = '/contact';

    /**
     * @return array<string, mixed>
     */
    public static function workspaceSummary(EmployerProfile $profile): array
    {
        $base = EmployerSubmission::query()->where('employer_profile_id', $profile->id);

        $totalSubmissions = (clone $base)->count();
        $activeRequirements = (clone $base)
            ->whereNotNull('job_id')
            ->distinct()
            ->count('job_id');

        $awaitingResponse = (clone $base)
            ->whereIn('status', [
                EmployerSubmissionStatus::Submitted,
                EmployerSubmissionStatus::UnderReview,
            ])
            ->count();

        $selected = (clone $base)
            ->where('status', EmployerSubmissionStatus::Selected)
            ->count();

        return [
            'active_requirements' => $activeRequirements,
            'candidate_submissions' => $totalSubmissions,
            'selected_count' => $selected,
            'pending_review_count' => $awaitingResponse,
        ];
    }

    /**
     * Jobs with at least one OurNear submission to this employer.
     *
     * @return list<array<string, mixed>>
     */
    public static function activeRequirements(EmployerProfile $profile, int $limit = 12): array
    {
        $jobIds = EmployerSubmission::query()
            ->where('employer_profile_id', $profile->id)
            ->whereNotNull('job_id')
            ->select('job_id')
            ->distinct()
            ->pluck('job_id');

        if ($jobIds->isEmpty()) {
            return [];
        }

        $countsByJob = EmployerSubmission::query()
            ->where('employer_profile_id', $profile->id)
            ->whereIn('job_id', $jobIds)
            ->selectRaw('job_id, status, COUNT(*) as aggregate')
            ->groupBy('job_id', 'status')
            ->get()
            ->groupBy('job_id');

        $latestByJob = EmployerSubmission::query()
            ->where('employer_profile_id', $profile->id)
            ->whereIn('job_id', $jobIds)
            ->selectRaw('job_id, MAX(submitted_at) as latest_submitted')
            ->groupBy('job_id')
            ->pluck('latest_submitted', 'job_id');

        return Job::query()
            ->whereIn('id', $jobIds)
            ->where('employer_id', $profile->id)
            ->orderByDesc('updated_at')
            ->limit($limit)
            ->get()
            ->map(function (Job $job) use ($countsByJob, $latestByJob) {
                $rows = $countsByJob->get($job->id, collect());
                $submissionCount = (int) $rows->sum('aggregate');
                $statusCounts = [];
                $dominantStatus = null;
                $dominantCount = 0;

                foreach ($rows as $row) {
                    $status = $row->status instanceof EmployerSubmissionStatus
                        ? $row->status
                        : EmployerSubmissionStatus::tryFrom((string) $row->status);
                    if (! $status instanceof EmployerSubmissionStatus) {
                        continue;
                    }
                    $count = (int) $row->aggregate;
                    $statusCounts[$status->value] = $count;
                    if ($count > $dominantCount) {
                        $dominantCount = $count;
                        $dominantStatus = $status;
                    }
                }

                $statusLabel = $dominantStatus instanceof EmployerSubmissionStatus
                    ? RecruitmentWorkspace::responseLabel($dominantStatus)
                    : null;

                return [
                    'job_id' => $job->id,
                    'title' => $job->title,
                    'slug' => $job->slug,
                    'business_id' => $job->business_id,
                    'location' => $job->displayLocation(),
                    'vacancy' => $job->vacancy,
                    'created_at' => $job->created_at?->toDateTimeString(),
                    'job_status_label' => $job->statusLabel(),
                    'submission_count' => $submissionCount,
                    'status_label' => $statusLabel,
                    'status_counts' => $statusCounts,
                    'latest_submitted_at' => $latestByJob->get($job->id),
                    'href' => route('account.employer.submissions.requirement', $job, false),
                    'next_action' => $submissionCount > 0
                        ? 'Review '.$submissionCount.' submitted candidate'.($submissionCount === 1 ? '' : 's')
                        : null,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return list<array{key: string, label: string, at: string|null}>
     */
    public static function employerTimeline(EmployerSubmission $submission): array
    {
        $submission->loadMissing(['activities']);

        $events = [];

        if ($submission->submitted_at) {
            $events[] = [
                'key' => 'submitted',
                'label' => 'Submitted to your company',
                'at' => $submission->submitted_at->toDateTimeString(),
            ];
        }

        foreach ($submission->activities as $activity) {
            if (! $activity instanceof RecruitmentActivity) {
                continue;
            }

            if (filled($activity->to_status)) {
                $status = EmployerSubmissionStatus::tryFrom((string) $activity->to_status);
                $label = $status instanceof EmployerSubmissionStatus
                    ? RecruitmentWorkspace::responseLabel($status)
                    : (string) $activity->to_status;
                $events[] = [
                    'key' => 'status-'.$activity->id,
                    'label' => $label,
                    'at' => $activity->created_at?->toDateTimeString(),
                ];
            }
        }

        return $events;
    }

    /**
     * @return Builder<EmployerSubmission>
     */
    public static function ownedSubmissionsQuery(EmployerProfile $profile): Builder
    {
        return EmployerSubmission::query()
            ->where('employer_profile_id', $profile->id)
            ->with(['application.user.candidateProfile', 'job']);
    }
}
