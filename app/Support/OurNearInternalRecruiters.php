<?php

namespace App\Support;

use App\Enums\StaffRoleSlug;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
/**
 * OurNear internal recruiters (admin staff). Not employer account users.
 */
class OurNearInternalRecruiters
{
    /**
     * @return list<StaffRoleSlug>
     */
    public static function eligibleStaffRoleSlugs(): array
    {
        return [
            StaffRoleSlug::SuperAdmin,
            StaffRoleSlug::Admin,
            StaffRoleSlug::RecruitmentManager,
            StaffRoleSlug::Recruiter,
            StaffRoleSlug::HrOperations,
        ];
    }

    /**
     * @return Builder<User>
     */
    public static function query(): Builder
    {
        $slugs = array_map(fn (StaffRoleSlug $role) => $role->value, self::eligibleStaffRoleSlugs());

        return User::query()
            ->where('role', UserRole::Admin->value)
            ->where('is_active', true)
            ->whereHas('roles', fn (Builder $role) => $role->whereIn('slug', $slugs))
            ->orderBy('name');
    }

    public static function shouldScopeTalentPoolWorkspace(User $user): bool
    {
        return self::canActAsInternalRecruiter($user)
            && $user->hasStaffRole(StaffRoleSlug::Recruiter->value)
            && ! $user->hasStaffRole(StaffRoleSlug::SuperAdmin->value)
            && ! $user->hasStaffRole(StaffRoleSlug::RecruitmentManager->value)
            && ! $user->hasStaffRole(StaffRoleSlug::Admin->value);
    }

    public static function canActAsInternalRecruiter(User $user): bool
    {
        if (! $user->isAdmin() || ! $user->is_active) {
            return false;
        }

        foreach (self::eligibleStaffRoleSlugs() as $slug) {
            if ($user->hasStaffRole($slug->value)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<array{id: int, name: string}>
     */
    public static function optionsForAdmin(): array
    {
        return self::query()
            ->get(['id', 'name'])
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<mixed>
     */
    public static function assignedRecruiterIdRules(): array
    {
        return [
            'required',
            'integer',
            'exists:users,id',
            function (string $attribute, mixed $value, \Closure $fail): void {
                $user = User::query()->find((int) $value);
                if (! $user instanceof User || ! self::canActAsInternalRecruiter($user)) {
                    $fail('Select an eligible OurNear internal recruiter.');
                }
            },
        ];
    }
}
