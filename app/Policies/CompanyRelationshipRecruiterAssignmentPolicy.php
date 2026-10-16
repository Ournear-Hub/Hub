<?php

namespace App\Policies;

use App\Enums\PermissionName;
use App\Enums\CompanyRelationshipRecruiterAssignmentStatus;
use App\Models\CompanyRelationshipRecruiterAssignment;
use App\Models\User;
use App\Support\OurNearInternalRecruiters;

class CompanyRelationshipRecruiterAssignmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(PermissionName::EmployersView->value)
            || $user->hasPermission(PermissionName::EmployersManage->value);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission(PermissionName::EmployersManage->value);
    }

    public function view(User $user, CompanyRelationshipRecruiterAssignment $assignment): bool
    {
        if ($this->viewAny($user)) {
            return true;
        }

        return OurNearInternalRecruiters::canActAsInternalRecruiter($user)
            && $assignment->status === CompanyRelationshipRecruiterAssignmentStatus::Active
            && (int) $assignment->assigned_recruiter_user_id === (int) $user->id;
    }
}
