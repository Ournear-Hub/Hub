<?php

namespace App\Support;

use App\Models\Application;
use App\Models\EmployerSubmission;
use App\Models\StaffingCandidateSubmission;
use App\Models\TalentPoolCompanySubmission;
use App\Models\User;
use App\Models\CandidateResume;
use App\Services\ApplicationResumeDeliveryService;
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
        $atsStage = AtsStage::for($application);
        $resumeDelivery = app(ApplicationResumeDeliveryService::class);
        $resumeAvailable = $resumeDelivery->snapshotAvailable($application);

        return [
            'id' => $application->id,
            'application_id' => $application->id,
            'business_id' => $application->business_id,
            'name' => $application->candidateDisplayName(),
            'photo_url' => $user?->photoUrl(),
            'username' => $shareEnabled ? $user?->username : null,
            'designation' => $profile?->current_designation,
            'location' => $profile?->location ?: $application->current_location,
            'experience' => $profile?->experience ?: $application->experience,
            'qualification' => $profile?->qualification ?: $application->qualification,
            'status' => $application->status->value,
            'status_label' => $application->status->toCandidateStatusLabel(),
            'ats_stage' => $atsStage['value'],
            'ats_stage_label' => $atsStage['label'],
            'pipeline_stage_label' => EmployerApplicationPipeline::stageLabelForApplication($application->status),
            'applied_at' => $application->created_at?->toDateTimeString(),
            'has_resume' => $resumeAvailable,
            'resume_url' => $resumeAvailable
                ? route('account.employer.applications.resume', $application, false)
                : null,
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

    /**
     * @return array<string, mixed>
     */
    public static function fromTalentPoolSubmission(TalentPoolCompanySubmission $submission): array
    {
        $submission->loadMissing(['candidateUser.candidateProfile', 'employerProfile']);
        $user = $submission->candidateUser;
        $profile = $user?->candidateProfile;
        $shareEnabled = $profile?->shareAllows('enabled') === true;
        $resume = $profile instanceof \App\Models\CandidateProfile
            ? CandidateResume::query()
                ->where('candidate_profile_id', $profile->id)
                ->orderByDesc('is_primary')
                ->orderByDesc('id')
                ->first()
            : null;
        $hasResume = $resume !== null;

        return [
            'id' => $submission->id,
            'submission_id' => $submission->id,
            'business_id' => $submission->business_id,
            'record_type' => 'talent_pool_company_submission',
            'name' => $user?->name ?? 'Candidate',
            'photo_url' => $user?->photoUrl(),
            'username' => $shareEnabled ? $user?->username : null,
            'designation' => $profile?->current_designation,
            'location' => $profile?->classifiedLocation?->name ?: $profile?->location,
            'experience' => $profile?->classifiedExperienceRange?->name ?: $profile?->experience,
            'qualification' => $profile?->classifiedQualification?->name ?: $profile?->qualification,
            'status' => $submission->status->value,
            'status_label' => RecruitmentWorkspace::responseLabel($submission->status),
            'submitted_at' => $submission->submitted_at?->toDateTimeString(),
            'has_resume' => $hasResume,
            'resume_url' => $hasResume
                ? route('account.coordinated-talent-pool.resume', $submission, false)
                : null,
            'public_profile_url' => $shareEnabled && is_string($user?->username) && $user->username !== ''
                ? route('profiles.show', ['user' => $user->username], false)
                : null,
            'submission_href' => route('account.coordinated-talent-pool.show', $submission, false),
            'source_label' => 'Submitted by OurNear Talent Pool coordination (not a job application).',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function fromStaffingSubmission(StaffingCandidateSubmission $submission): array
    {
        $submission->loadMissing(['candidateUser.candidateProfile', 'employerEnquiry']);
        $user = $submission->candidateUser;
        $profile = $user?->candidateProfile;
        $shareEnabled = $profile?->shareAllows('enabled') === true;
        $resume = $profile instanceof \App\Models\CandidateProfile
            ? CandidateResume::query()
                ->where('candidate_profile_id', $profile->id)
                ->orderByDesc('is_primary')
                ->orderByDesc('id')
                ->first()
            : null;
        $hasResume = $resume !== null;

        return [
            'id' => $submission->id,
            'submission_id' => $submission->id,
            'business_id' => $submission->business_id,
            'record_type' => 'staffing_candidate_submission',
            'name' => $user?->name ?? 'Candidate',
            'photo_url' => $user?->photoUrl(),
            'username' => $shareEnabled ? $user?->username : null,
            'designation' => $profile?->current_designation,
            'location' => $profile?->classifiedLocation?->name ?: $profile?->location,
            'experience' => $profile?->classifiedExperienceRange?->name ?: $profile?->experience,
            'qualification' => $profile?->classifiedQualification?->name ?: $profile?->qualification,
            'status' => $submission->status->value,
            'status_label' => $submission->status->label(),
            'submitted_at' => $submission->submitted_at?->toDateTimeString(),
            'client_feedback' => $submission->client_feedback,
            'joining_date' => $submission->joining_date?->toDateString(),
            'has_resume' => $hasResume,
            'resume_url' => $hasResume
                ? route('account.staffing-submissions.resume', $submission, false)
                : null,
            'public_profile_url' => $shareEnabled && is_string($user?->username) && $user->username !== ''
                ? route('profiles.show', ['user' => $user->username], false)
                : null,
            'submission_href' => route('account.staffing-submissions.show', $submission, false),
            'employer_enquiry_id' => $submission->employer_enquiry_id,
            'source_label' => 'Submitted by OurNear Staffing coordination (not a job application).',
            'allowed_client_statuses' => array_map(
                fn ($status) => ['value' => $status->value, 'label' => $status->label()],
                $submission->status->allowedClientTransitions(),
            ),
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
