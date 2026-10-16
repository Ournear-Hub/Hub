<?php

namespace App\Policies;

use App\Enums\PermissionName;
use App\Models\RecruiterTalentPoolAssignment;
use App\Models\User;
use App\Support\OurNearInternalRecruiters;
use App\Support\RecruitingEmployerAccounts;

class RecruiterTalentPoolAssignmentPolicy
{
    public function viewAny(User $user): bool
    {
        if ($user->hasPermission(PermissionName::CandidatesView->value)
            || $user->hasPermission(PermissionName::CandidatesManage->value)) {
            return true;
        }

        return $user->canAccessHiringWorkspace();
    }

    public function view(User $user, RecruiterTalentPoolAssignment $assignment): bool
    {
        if ($user->hasPermission(PermissionName::CandidatesView->value)
            || $user->hasPermission(PermissionName::CandidatesManage->value)) {
            return true;
        }

        return $this->assignedRecruiter($user, $assignment)
            || $this->companyOwnsAssignment($user, $assignment);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission(PermissionName::CandidatesManage->value);
    }

    public function updateStatus(User $user, RecruiterTalentPoolAssignment $assignment): bool
    {
        if (OurNearInternalRecruiters::shouldScopeTalentPoolWorkspace($user)) {
            return $this->assignedRecruiter($user, $assignment);
        }

        if ($user->hasPermission(PermissionName::CandidatesManage->value)) {
            return true;
        }

        return $this->assignedRecruiter($user, $assignment);
    }

    public function archive(User $user, RecruiterTalentPoolAssignment $assignment): bool
    {
        return $this->updateStatus($user, $assignment);
    }

    public function downloadResume(User $user, RecruiterTalentPoolAssignment $assignment): bool
    {
        if (! $this->view($user, $assignment)) {
            return false;
        }

        return $assignment->application_id !== null;
    }

    private function assignedRecruiter(User $user, RecruiterTalentPoolAssignment $assignment): bool
    {
        if ((int) $assignment->assigned_recruiter_user_id !== (int) $user->id) {
            return false;
        }

        if (OurNearInternalRecruiters::canActAsInternalRecruiter($user)) {
            return true;
        }

        return $user->canAccessHiringWorkspace();
    }

    private function companyOwnsAssignment(User $user, RecruiterTalentPoolAssignment $assignment): bool
    {
        if (! $user->canAccessHiringWorkspace()) {
            return false;
        }

        $profile = $user->employerProfile;
        if ($profile === null) {
            return false;
        }

        $assignment->loadMissing(['job', 'employerEnquiry']);

        if ($assignment->job !== null && (int) $assignment->job->employer_id === (int) $profile->id) {
            return true;
        }

        return $assignment->employerEnquiry !== null
            && (int) $assignment->employerEnquiry->user_id === (int) $user->id;
    }
}
