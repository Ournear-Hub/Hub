<?php

namespace App\Services;

use App\Enums\EmployerStaffingAccessStatus;
use App\Enums\PermissionName;
use App\Models\EmployerProfile;
use App\Models\EmployerStaffingAccessEvent;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EmployerStaffingAccessService
{
    public function __construct(
        private EmployerStaffingAccessNotificationService $notifications,
    ) {}

    public function requestAccess(User $companyUser, EmployerProfile $profile): EmployerProfile
    {
        abort_unless((int) $profile->user_id === (int) $companyUser->id, 403);

        if ($profile->staffing_access_status === EmployerStaffingAccessStatus::Approved) {
            throw ValidationException::withMessages([
                'staffing_access' => 'Staffing access is already approved for this company.',
            ]);
        }

        if ($profile->staffing_access_status === EmployerStaffingAccessStatus::PendingApproval) {
            throw ValidationException::withMessages([
                'staffing_access' => 'A staffing access request is already pending review.',
            ]);
        }

        return DB::transaction(function () use ($profile, $companyUser) {
            $from = $profile->staffing_access_status ?? EmployerStaffingAccessStatus::NotRequested;
            $to = EmployerStaffingAccessStatus::PendingApproval;

            $profile->update([
                'staffing_access_status' => $to,
                'staffing_access_requested_at' => now(),
                'staffing_access_rejection_reason' => null,
            ]);

            $this->recordEvent($profile, $from, $to, $companyUser, 'Company requested Staffing module access.');
            $fresh = $profile->fresh() ?? $profile;
            $this->notifications->accessRequested($fresh, $companyUser);

            return $fresh;
        });
    }

    public function approve(User $admin, EmployerProfile $profile, ?string $notes = null): EmployerProfile
    {
        $this->assertAdmin($admin);

        $updated = $this->transition($profile, $admin, EmployerStaffingAccessStatus::Approved, $notes);
        $this->notifications->accessApproved($updated);

        return $updated;
    }

    public function reject(User $admin, EmployerProfile $profile, string $reason): EmployerProfile
    {
        $this->assertAdmin($admin);
        $reason = trim($reason);
        if ($reason === '') {
            throw ValidationException::withMessages([
                'reason' => 'Provide a rejection reason.',
            ]);
        }

        return DB::transaction(function () use ($profile, $admin, $reason) {
            $from = $profile->staffing_access_status ?? EmployerStaffingAccessStatus::NotRequested;
            $to = EmployerStaffingAccessStatus::Rejected;

            $profile->update([
                'staffing_access_status' => $to,
                'staffing_access_decided_at' => now(),
                'staffing_access_decided_by_user_id' => $admin->id,
                'staffing_access_rejection_reason' => $reason,
            ]);

            $this->recordEvent($profile, $from, $to, $admin, $reason);
            $fresh = $profile->fresh() ?? $profile;
            $this->notifications->accessRejected($fresh, $reason);

            return $fresh;
        });
    }

    public function revoke(User $admin, EmployerProfile $profile, ?string $notes = null): EmployerProfile
    {
        $this->assertAdmin($admin);

        return $this->transition($profile, $admin, EmployerStaffingAccessStatus::Revoked, $notes);
    }

    private function transition(
        EmployerProfile $profile,
        User $admin,
        EmployerStaffingAccessStatus $to,
        ?string $notes,
    ): EmployerProfile {
        return DB::transaction(function () use ($profile, $admin, $to, $notes) {
            $from = $profile->staffing_access_status ?? EmployerStaffingAccessStatus::NotRequested;

            $profile->update([
                'staffing_access_status' => $to,
                'staffing_access_decided_at' => now(),
                'staffing_access_decided_by_user_id' => $admin->id,
                'staffing_access_rejection_reason' => $to === EmployerStaffingAccessStatus::Approved ? null : $profile->staffing_access_rejection_reason,
            ]);

            $this->recordEvent($profile, $from, $to, $admin, $notes);

            return $profile->fresh() ?? $profile;
        });
    }

    private function recordEvent(
        EmployerProfile $profile,
        EmployerStaffingAccessStatus $from,
        EmployerStaffingAccessStatus $to,
        User $actor,
        ?string $notes,
    ): void {
        EmployerStaffingAccessEvent::query()->create([
            'employer_profile_id' => $profile->id,
            'from_status' => $from->value,
            'to_status' => $to->value,
            'notes' => $notes,
            'actor_id' => $actor->id,
        ]);
    }

    private function assertAdmin(User $admin): void
    {
        if (! $admin->hasPermission(PermissionName::CandidatesManage->value)
            && ! $admin->hasPermission(PermissionName::EmployersManage->value)) {
            throw ValidationException::withMessages([
                'actor' => 'Only authorized admin staff can manage staffing access.',
            ]);
        }
    }
}
