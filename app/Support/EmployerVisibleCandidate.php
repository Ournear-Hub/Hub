<?php

namespace App\Support;

use App\Models\Application;
use App\Models\EmployerSubmission;
use App\Models\User;
use App\Support\RecruitmentWorkspace;

class EmployerVisibleCandidate
{
    /**
     * @return array<string, mixed>
     */
    public static function fromApplication(Application $application): array
    {
        $application->loadMissing(['user.candidateProfile', 'job']);

        $user = $application->user;
        $profile = $user?->candidateProfile;
        $shareEnabled = $profile?->shareAllows('enabled') === true;

        return [
            'id' => $application->id,
            'application_id' => $application->id,
            'business_id' => $application->business_id,
            'name' => $user?->name ?: $application->full_name,
            'photo_url' => $user?->photoUrl(),
            'username' => $shareEnabled ? $user?->username : null,
            'designation' => $profile?->current_designation,
            'location' => $profile?->location ?: $application->current_location,
            'experience' => $profile?->experience ?: $application->experience,
            'qualification' => $profile?->qualification ?: $application->qualification,
            'status' => $application->status->value,
            'status_label' => $application->status->toCandidateStatusLabel(),
            'applied_at' => $application->created_at?->toDateTimeString(),
            'has_resume' => filled($application->getRawOriginal('resume_path')),
            'resume_url' => route('account.employer.applications.resume', $application, false),
            'public_profile_url' => $shareEnabled && is_string($user?->username) && $user->username !== ''
                ? route('profiles.show', ['user' => $user->username], false)
                : null,
            'job' => $application->job ? [
                'title' => $application->job->title,
                'business_id' => $application->job->business_id,
                'slug' => $application->job->slug,
            ] : null,
            'application_href' => $application->job
                ? route('account.employer.jobs.application', [$application->job, $application], false)
                : null,
            'application_contact' => self::applicationContactSnapshot($application),
        ];
    }

    /**
     * Contact details from the immutable application snapshot (not the candidate account).
     *
     * @return array{available: bool, email: string|null, mobile: string|null, source_label: string}
     */
    public static function applicationContactSnapshot(Application $application): array
    {
        $email = is_string($application->email) && trim($application->email) !== ''
            ? trim($application->email)
            : null;
        $mobile = is_string($application->mobile) && trim($application->mobile) !== ''
            ? trim($application->mobile)
            : null;

        return [
            'available' => $email !== null || $mobile !== null,
            'email' => $email,
            'mobile' => $mobile,
            'source_label' => 'Provided on the application snapshot when the candidate applied.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function fromSubmission(EmployerSubmission $submission): array
    {
        $submission->loadMissing(['application.user.candidateProfile', 'job']);

        $application = $submission->application;
        $base = $application instanceof Application
            ? self::fromApplication($application)
            : [
                'id' => $submission->id,
                'application_id' => null,
                'business_id' => $submission->business_id,
                'name' => 'Candidate',
                'photo_url' => null,
                'username' => null,
                'designation' => null,
                'location' => null,
                'experience' => null,
                'qualification' => null,
                'has_resume' => false,
                'resume_url' => null,
                'public_profile_url' => null,
                'job' => null,
            ];

        return [
            ...$base,
            'id' => $submission->id,
            'submission_id' => $submission->id,
            'business_id' => $submission->business_id,
            'status' => $submission->status->value,
            'status_label' => RecruitmentWorkspace::responseLabel($submission->status),
            'submitted_at' => $submission->submitted_at?->toDateTimeString(),
            'job' => $submission->job ? [
                'title' => $submission->job->title,
                'business_id' => $submission->job->business_id,
                'slug' => $submission->job->slug,
            ] : $base['job'],
            'submission_href' => route('account.employer.submissions.show', $submission, false),
            'requirement_href' => $submission->job
                ? route('account.employer.submissions.requirement', $submission->job, false)
                : null,
        ];
    }

    public static function employerOwnsSubmission(User $user, Application $application): bool
    {
        $profileId = $user->employerProfile?->id;
        if ($profileId === null) {
            return false;
        }

        return $application->submissions()
            ->where('employer_profile_id', $profileId)
            ->exists();
    }
}
