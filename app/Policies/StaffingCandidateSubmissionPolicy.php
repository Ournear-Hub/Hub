<?php

namespace App\Policies;

use App\Enums\EmployerStaffingAccessStatus;
use App\Enums\PermissionName;
use App\Models\StaffingCandidateSubmission;
use App\Models\User;

class StaffingCandidateSubmissionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(PermissionName::EmployersView->value)
            || $user->hasPermission(PermissionName::CandidatesManage->value)
            || ($user->employerProfile?->staffing_access_status === EmployerStaffingAccessStatus::Approved);
    }

    public function view(User $user, StaffingCandidateSubmission $submission): bool
    {
        if ($user->hasPermission(PermissionName::EmployersView->value)
            || $user->hasPermission(PermissionName::CandidatesManage->value)) {
            return true;
        }

        $submission->loadMissing('employerEnquiry');

        return $submission->employerEnquiry !== null
            && $submission->employerEnquiry->user_id !== null
            && (int) $submission->employerEnquiry->user_id === (int) $user->id
            && $user->employerProfile?->staffing_access_status === EmployerStaffingAccessStatus::Approved;
    }

    public function updateClientDecision(User $user, StaffingCandidateSubmission $submission): bool
    {
        $submission->loadMissing('employerEnquiry');

        return $submission->employerEnquiry !== null
            && $submission->employerEnquiry->user_id !== null
            && (int) $submission->employerEnquiry->user_id === (int) $user->id
            && $user->employerProfile?->staffing_access_status === EmployerStaffingAccessStatus::Approved;
    }

    public function submitToClient(User $user): bool
    {
        return $user->hasPermission(PermissionName::CandidatesManage->value);
    }

    public function downloadResume(User $user, StaffingCandidateSubmission $submission): bool
    {
        return $this->view($user, $submission);
    }
}
