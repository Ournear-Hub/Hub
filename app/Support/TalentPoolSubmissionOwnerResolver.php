<?php

namespace App\Support;

use App\Enums\CompanyRelationshipRecruiterAssignmentStatus;
use App\Enums\RecruiterTalentPoolAssignmentStatus;
use App\Enums\TalentPoolSubmissionOwnerResolution;
use App\Models\CompanyRelationshipRecruiterAssignment;
use App\Models\EmployerProfile;
use App\Models\RecruiterTalentPoolAssignment;
use App\Models\User;

/**
 * Determines responsible OurNear recruiter for a Talent Pool company submission without mutating assignments.
 *
 * Rule: candidate active assignment owner wins; else company relationship owner; admin may pass explicit override.
 * When candidate and company owners differ, responsible = candidate owner; both IDs are returned for persistence.
 */
class TalentPoolSubmissionOwnerResolver
{
    /**
     * @return array{
     *     responsible_recruiter_user_id: int|null,
     *     candidate_owner_recruiter_user_id: int|null,
     *     company_owner_recruiter_user_id: int|null,
     *     owner_resolution: TalentPoolSubmissionOwnerResolution,
     *     recruiter_talent_pool_assignment_id: int|null,
     * }
     */
    public function resolve(
        User $candidate,
        EmployerProfile $employer,
        ?User $explicitRecruiter = null,
        ?RecruiterTalentPoolAssignment $assignment = null,
    ): array {
        $candidateOwnerId = null;
        $assignmentId = null;

        if ($assignment !== null && (int) $assignment->candidate_user_id === (int) $candidate->id) {
            $candidateOwnerId = (int) $assignment->assigned_recruiter_user_id;
            $assignmentId = (int) $assignment->id;
        } else {
            $activeAssignment = RecruiterTalentPoolAssignment::query()
                ->where('candidate_user_id', $candidate->id)
                ->whereNull('archived_at')
                ->whereIn('status', [
                    RecruiterTalentPoolAssignmentStatus::PendingReview->value,
                    RecruiterTalentPoolAssignmentStatus::InProgress->value,
                ])
                ->latest('id')
                ->first();

            if ($activeAssignment !== null) {
                $candidateOwnerId = (int) $activeAssignment->assigned_recruiter_user_id;
                $assignmentId = (int) $activeAssignment->id;
            }
        }

        $companyOwnerId = CompanyRelationshipRecruiterAssignment::query()
            ->where('employer_profile_id', $employer->id)
            ->where('status', CompanyRelationshipRecruiterAssignmentStatus::Active->value)
            ->latest('id')
            ->value('assigned_recruiter_user_id');

        $companyOwnerId = $companyOwnerId !== null ? (int) $companyOwnerId : null;

        if ($explicitRecruiter !== null && OurNearInternalRecruiters::canActAsInternalRecruiter($explicitRecruiter)) {
            return [
                'responsible_recruiter_user_id' => (int) $explicitRecruiter->id,
                'candidate_owner_recruiter_user_id' => $candidateOwnerId,
                'company_owner_recruiter_user_id' => $companyOwnerId,
                'owner_resolution' => TalentPoolSubmissionOwnerResolution::ExplicitAdmin,
                'recruiter_talent_pool_assignment_id' => $assignmentId,
            ];
        }

        if ($candidateOwnerId !== null && $companyOwnerId !== null && $candidateOwnerId !== $companyOwnerId) {
            return [
                'responsible_recruiter_user_id' => $candidateOwnerId,
                'candidate_owner_recruiter_user_id' => $candidateOwnerId,
                'company_owner_recruiter_user_id' => $companyOwnerId,
                'owner_resolution' => TalentPoolSubmissionOwnerResolution::CandidateOverCompanyConflict,
                'recruiter_talent_pool_assignment_id' => $assignmentId,
            ];
        }

        if ($candidateOwnerId !== null) {
            return [
                'responsible_recruiter_user_id' => $candidateOwnerId,
                'candidate_owner_recruiter_user_id' => $candidateOwnerId,
                'company_owner_recruiter_user_id' => $companyOwnerId,
                'owner_resolution' => TalentPoolSubmissionOwnerResolution::CandidateAssignment,
                'recruiter_talent_pool_assignment_id' => $assignmentId,
            ];
        }

        if ($companyOwnerId !== null) {
            return [
                'responsible_recruiter_user_id' => $companyOwnerId,
                'candidate_owner_recruiter_user_id' => null,
                'company_owner_recruiter_user_id' => $companyOwnerId,
                'owner_resolution' => TalentPoolSubmissionOwnerResolution::CompanyAssignment,
                'recruiter_talent_pool_assignment_id' => $assignmentId,
            ];
        }

        return [
            'responsible_recruiter_user_id' => null,
            'candidate_owner_recruiter_user_id' => null,
            'company_owner_recruiter_user_id' => null,
            'owner_resolution' => TalentPoolSubmissionOwnerResolution::Unassigned,
            'recruiter_talent_pool_assignment_id' => $assignmentId,
        ];
    }
}
