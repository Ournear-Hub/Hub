<?php

namespace App\Policies;

use App\Enums\PermissionName;
use App\Models\TalentPoolCompanySubmission;
use App\Models\User;
use App\Support\OurNearInternalRecruiters;

class TalentPoolCompanySubmissionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(PermissionName::CandidatesView->value)
            || $user->hasPermission(PermissionName::CandidatesManage->value)
            || $user->hasPermission(PermissionName::SubmissionsView->value)
            || $user->canAccessHiringWorkspace();
    }

    public function view(User $user, TalentPoolCompanySubmission $submission): bool
    {
        if (OurNearInternalRecruiters::shouldScopeTalentPoolWorkspace($user)) {
            return (int) $submission->responsible_recruiter_user_id === (int) $user->id
                || (int) $submission->candidate_owner_recruiter_user_id === (int) $user->id
                || (int) $submission->company_owner_recruiter_user_id === (int) $user->id;
        }

        if ($user->hasPermission(PermissionName::CandidatesManage->value)
            || $user->hasPermission(PermissionName::SubmissionsManage->value)
            || $user->hasPermission(PermissionName::CandidatesView->value)
            || $user->hasPermission(PermissionName::SubmissionsView->value)) {
            return true;
        }

        if (OurNearInternalRecruiters::canActAsInternalRecruiter($user)) {
            if ((int) $submission->responsible_recruiter_user_id === (int) $user->id) {
                return true;
            }
            if ((int) $submission->candidate_owner_recruiter_user_id === (int) $user->id) {
                return true;
            }
            if ((int) $submission->company_owner_recruiter_user_id === (int) $user->id) {
                return true;
            }
        }

        return $user->canAccessHiringWorkspace()
            && (int) $user->employerProfile?->id === (int) $submission->employer_profile_id;
    }

    public function create(User $user): bool
    {
        if (OurNearInternalRecruiters::shouldScopeTalentPoolWorkspace($user)) {
            return true;
        }

        if ($user->hasPermission(PermissionName::CandidatesManage->value)
            || $user->hasPermission(PermissionName::SubmissionsManage->value)) {
            return true;
        }

        return OurNearInternalRecruiters::canActAsInternalRecruiter($user);
    }

    public function updateEmployerStatus(User $user, TalentPoolCompanySubmission $submission): bool
    {
        return $user->canAccessHiringWorkspace()
            && (int) $user->employerProfile?->id === (int) $submission->employer_profile_id;
    }

    public function downloadResume(User $user, TalentPoolCompanySubmission $submission): bool
    {
        return $this->view($user, $submission);
    }
}
