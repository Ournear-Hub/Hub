<?php

namespace App\Support;

use App\Enums\CompanyRelationshipRecruiterAssignmentStatus;
use App\Enums\PermissionName;
use App\Models\CompanyRelationshipRecruiterAssignment;
use App\Models\EmployerProfile;
use App\Models\RecruiterTalentPoolAssignment;
use App\Models\StaffingCandidateSubmission;
use App\Models\TalentPoolCompanySubmission;
use App\Models\User;
use App\Enums\EmployerStaffingAccessStatus;

final class RecruiterOperationalAccess
{
    public static function canViewSubmissionOperational(User $user, TalentPoolCompanySubmission $submission): bool
    {
        if ($user->hasPermission(PermissionName::CandidatesView->value)
            || $user->hasPermission(PermissionName::SubmissionsView->value)) {
            return true;
        }

        if (OurNearInternalRecruiters::canActAsInternalRecruiter($user)) {
            return self::ownsSubmission($user, $submission);
        }

        return $user->canAccessHiringWorkspace()
            && (int) $user->employerProfile?->id === (int) $submission->employer_profile_id;
    }

    public static function canManageSubmissionOperational(User $user, TalentPoolCompanySubmission $submission): bool
    {
        if ($user->hasPermission(PermissionName::CandidatesManage->value)
            || $user->hasPermission(PermissionName::SubmissionsManage->value)) {
            return true;
        }

        return OurNearInternalRecruiters::canActAsInternalRecruiter($user)
            && self::ownsSubmission($user, $submission);
    }

    public static function canManageEmployerOperational(User $user, EmployerProfile $employer): bool
    {
        if ($user->hasPermission(PermissionName::EmployersManage->value)
            || $user->hasPermission(PermissionName::CandidatesManage->value)) {
            return true;
        }

        if (! OurNearInternalRecruiters::canActAsInternalRecruiter($user)) {
            return false;
        }

        return CompanyRelationshipRecruiterAssignment::query()
            ->where('employer_profile_id', $employer->id)
            ->where('assigned_recruiter_user_id', $user->id)
            ->where('status', CompanyRelationshipRecruiterAssignmentStatus::Active->value)
            ->exists();
    }

    public static function canManageAssignmentOperational(User $user, RecruiterTalentPoolAssignment $assignment): bool
    {
        if ($user->hasPermission(PermissionName::CandidatesManage->value)) {
            return true;
        }

        return OurNearInternalRecruiters::canActAsInternalRecruiter($user)
            && (int) $assignment->assigned_recruiter_user_id === (int) $user->id;
    }

    public static function canViewInternalOperational(User $user): bool
    {
        return $user->isAdmin()
            && (
                $user->hasPermission(PermissionName::CandidatesView->value)
                || $user->hasPermission(PermissionName::SubmissionsView->value)
                || OurNearInternalRecruiters::canActAsInternalRecruiter($user)
            );
    }

    public static function canViewStaffingSubmissionOperational(User $user, StaffingCandidateSubmission $submission): bool
    {
        if ($user->hasPermission(PermissionName::EmployersView->value)
            || $user->hasPermission(PermissionName::CandidatesView->value)) {
            return true;
        }

        if (OurNearInternalRecruiters::canActAsInternalRecruiter($user)) {
            return self::ownsStaffingSubmission($user, $submission);
        }

        $submission->loadMissing('employerEnquiry');

        return $user->employerProfile?->staffing_access_status === EmployerStaffingAccessStatus::Approved
            && $submission->employerEnquiry !== null
            && (int) $submission->employerEnquiry->user_id === (int) $user->id;
    }

    public static function canManageStaffingSubmissionOperational(User $user, StaffingCandidateSubmission $submission): bool
    {
        if ($user->hasPermission(PermissionName::CandidatesManage->value)
            || $user->hasPermission(PermissionName::EmployersManage->value)) {
            return true;
        }

        return OurNearInternalRecruiters::canActAsInternalRecruiter($user)
            && self::ownsStaffingSubmission($user, $submission);
    }

    public static function canMessageOnStaffingSubmission(User $user, StaffingCandidateSubmission $submission): bool
    {
        if (self::canManageStaffingSubmissionOperational($user, $submission)) {
            return true;
        }

        $submission->loadMissing('employerEnquiry');

        return $user->employerProfile?->staffing_access_status === EmployerStaffingAccessStatus::Approved
            && $submission->employerEnquiry !== null
            && (int) $submission->employerEnquiry->user_id === (int) $user->id;
    }

    private static function ownsStaffingSubmission(User $user, StaffingCandidateSubmission $submission): bool
    {
        if ((int) $submission->responsible_recruiter_user_id === (int) $user->id) {
            return true;
        }

        if ((int) $submission->submitted_by_user_id === (int) $user->id) {
            return true;
        }

        return false;
    }

    private static function ownsSubmission(User $user, TalentPoolCompanySubmission $submission): bool
    {
        if ((int) $submission->responsible_recruiter_user_id === (int) $user->id) {
            return true;
        }

        if ((int) $submission->candidate_owner_recruiter_user_id === (int) $user->id) {
            return true;
        }

        return (int) $submission->company_owner_recruiter_user_id === (int) $user->id;
    }
}
