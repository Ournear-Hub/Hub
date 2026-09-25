<?php

namespace App\Services;

use App\Enums\AccountStatus;
use App\Enums\PreferredLanguage;
use App\Enums\SecurityActivityType;
use App\Enums\UserActivityType;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

class UserCrmService
{
    public function __construct(
        private UserManagementService $users,
        private UserActivityService $activities,
        private SecurityAuditService $audit,
        private UserPhotoService $photos,
        private CommunicationPreferenceService $preferences,
        private LoginLogService $loginLogs,
    ) {}

    public function setStatus(User $actor, User $target, AccountStatus $status): User
    {
        if ((int) $actor->id === (int) $target->id && ! $status->allowsSignIn()) {
            throw ValidationException::withMessages([
                'account_status' => 'You cannot change your own account to a sign-in blocked status.',
            ]);
        }

        if (! $status->allowsSignIn() && $target->isSuperAdmin() && $this->users->superAdminCount() <= 1) {
            throw ValidationException::withMessages([
                'account_status' => 'The last Super Admin cannot be blocked, suspended, or deactivated.',
            ]);
        }

        $previous = $target->accountStatus();
        if ($previous === $status) {
            return $target;
        }

        $target->forceFill([
            'account_status' => $status,
            'is_active' => $status->allowsSignIn(),
        ])->save();
        $target->forgetPermissionCache();

        $activityType = match ($status) {
            AccountStatus::Blocked => UserActivityType::Blocked,
            AccountStatus::Suspended => UserActivityType::Suspended,
            AccountStatus::Active => $previous === AccountStatus::Blocked
                ? UserActivityType::Unblocked
                : UserActivityType::Reactivated,
            AccountStatus::Deactivated, AccountStatus::Inactive => UserActivityType::Deactivated,
            default => UserActivityType::SecurityEvent,
        };

        $this->activities->record(
            $target,
            $activityType,
            'Account status changed to '.$status->label().'.',
            $actor,
            ['from' => $previous->value, 'to' => $status->value],
        );

        $securityType = match ($status) {
            AccountStatus::Blocked => SecurityActivityType::UserBlocked,
            AccountStatus::Suspended => SecurityActivityType::UserSuspended,
            AccountStatus::Active => SecurityActivityType::UserActivated,
            default => SecurityActivityType::UserDeactivated,
        };

        $this->audit->record(
            $securityType,
            'User '.$target->email.' status set to '.$status->label().'.',
            $actor,
            $target,
            ['from' => $previous->value, 'to' => $status->value],
        );

        if (! $status->allowsSignIn()) {
            $this->loginLogs->accountDisabled(request(), $target);
        }

        return $target->fresh(['roles', 'brand', 'adminProfile']) ?? $target;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function updateIdentity(User $actor, User $target, array $attributes, ?UploadedFile $photo = null, bool $removePhoto = false): User
    {
        if ($photo) {
            $previous = $target->photo_path;
            $target->photo_path = $this->photos->store($photo);
            $this->photos->delete($previous);
        } elseif ($removePhoto) {
            $this->photos->delete($target->photo_path);
            $target->photo_path = null;
        }

        $target->fill($attributes)->save();

        $this->activities->record(
            $target,
            UserActivityType::UpdatedProfile,
            'Profile details were updated.',
            $actor,
        );

        $this->audit->record(
            SecurityActivityType::UserUpdated,
            'User '.$target->email.' was updated.',
            $actor,
            $target,
        );

        return $target->fresh(['roles', 'brand', 'adminProfile']) ?? $target;
    }

    /**
     * @param  array<string, bool>  $channels
     */
    public function updatePreferences(User $actor, User $target, array $channels): void
    {
        $this->preferences->sync($target, $channels, 'admin');
        $this->activities->record(
            $target,
            UserActivityType::UpdatedProfile,
            'Communication preferences were updated.',
            $actor,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function languageOptions(): array
    {
        return array_map(fn (PreferredLanguage $language) => [
            'value' => $language->value,
            'label' => $language->label(),
        ], PreferredLanguage::cases());
    }

    /**
     * @return array<string, mixed>
     */
    public function statusOptions(): array
    {
        return array_map(fn (AccountStatus $status) => [
            'value' => $status->value,
            'label' => $status->label(),
        ], AccountStatus::cases());
    }
}
