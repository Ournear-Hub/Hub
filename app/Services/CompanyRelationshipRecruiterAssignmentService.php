<?php

namespace App\Services;

use App\Enums\CompanyRelationshipRecruiterAssignmentStatus;
use App\Models\CompanyRelationshipRecruiterAssignment;
use App\Models\EmployerProfile;
use App\Models\User;
use App\Support\OurNearInternalRecruiters;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CompanyRelationshipRecruiterAssignmentService
{
    public function assign(
        EmployerProfile $employer,
        User $recruiter,
        User $admin,
        ?string $notes = null,
    ): CompanyRelationshipRecruiterAssignment {
        return DB::transaction(function () use ($employer, $recruiter, $admin, $notes) {
            $this->assertAdminOperator($admin);
            $this->assertInternalRecruiter($recruiter);

            $active = CompanyRelationshipRecruiterAssignment::query()
                ->where('employer_profile_id', $employer->id)
                ->where('status', CompanyRelationshipRecruiterAssignmentStatus::Active->value)
                ->lockForUpdate()
                ->first();

            if ($active !== null) {
                if ((int) $active->assigned_recruiter_user_id === (int) $recruiter->id) {
                    return $active;
                }

                $this->endAssignment($active, $admin, 'Reassigned to another OurNear recruiter.');
            }

            $assignment = CompanyRelationshipRecruiterAssignment::query()->create([
                'employer_profile_id' => $employer->id,
                'assigned_recruiter_user_id' => $recruiter->id,
                'assigned_by_user_id' => $admin->id,
                'status' => CompanyRelationshipRecruiterAssignmentStatus::Active,
                'notes' => $notes,
            ]);

            app(TalentPoolCoordinationNotificationService::class)->companyRecruiterAssigned($assignment);

            return $assignment;
        });
    }

    public function endAssignment(
        CompanyRelationshipRecruiterAssignment $assignment,
        User $admin,
        ?string $notes = null,
    ): CompanyRelationshipRecruiterAssignment {
        return DB::transaction(function () use ($assignment, $admin, $notes) {
            $this->assertAdminOperator($admin);

            $assignment = CompanyRelationshipRecruiterAssignment::query()->lockForUpdate()->findOrFail($assignment->id);

            if ($assignment->status !== CompanyRelationshipRecruiterAssignmentStatus::Active) {
                return $assignment;
            }

            $assignment->forceFill([
                'status' => CompanyRelationshipRecruiterAssignmentStatus::Ended,
                'ended_at' => now(),
                'ended_by_user_id' => $admin->id,
                'notes' => $notes ?? $assignment->notes,
            ])->save();

            return $assignment->fresh() ?? $assignment;
        });
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function historyForEmployer(EmployerProfile $employer, int $limit = 20): array
    {
        return CompanyRelationshipRecruiterAssignment::query()
            ->where('employer_profile_id', $employer->id)
            ->with(['recruiterUser', 'assignedByUser', 'endedByUser'])
            ->latest('id')
            ->limit($limit)
            ->get()
            ->map(fn (CompanyRelationshipRecruiterAssignment $row) => $row->toAdminArray())
            ->values()
            ->all();
    }

    public function activeForEmployer(EmployerProfile $employer): ?CompanyRelationshipRecruiterAssignment
    {
        return CompanyRelationshipRecruiterAssignment::query()
            ->where('employer_profile_id', $employer->id)
            ->where('status', CompanyRelationshipRecruiterAssignmentStatus::Active->value)
            ->with(['recruiterUser', 'assignedByUser'])
            ->latest('id')
            ->first();
    }

    private function assertAdminOperator(User $admin): void
    {
        if (! $admin->isAdmin()) {
            throw ValidationException::withMessages([
                'actor' => 'Only authorized admin staff can manage company recruiter assignments.',
            ]);
        }
    }

    private function assertInternalRecruiter(User $recruiter): void
    {
        if (! OurNearInternalRecruiters::canActAsInternalRecruiter($recruiter)) {
            throw ValidationException::withMessages([
                'assigned_recruiter_user_id' => 'Select an eligible OurNear internal recruiter.',
            ]);
        }
    }
}
