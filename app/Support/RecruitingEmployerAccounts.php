<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Accounts that can act as recruiters in Talent Pool / hiring workflows.
 * Includes dual-role users (e.g. candidate + linked employer profile), not only UserRole::Employer.
 */
class RecruitingEmployerAccounts
{
    /**
     * @return Builder<User>
     */
    public static function query(): Builder
    {
        return User::query()
            ->whereHas('employerProfile', function (Builder $profile): void {
                $profile->whereNotNull('company_name')
                    ->where('company_name', '!=', '');
            })
            ->orderBy('name');
    }

    /**
     * @return list<array{id: int, name: string, employer_profile_id: int|null}>
     */
    public static function canActAsRecruiter(User $user): bool
    {
        if (! $user->canAccessHiringWorkspace()) {
            return false;
        }

        $user->loadMissing('employerProfile');
        $company = trim((string) ($user->employerProfile?->company_name ?? ''));

        return $company !== '';
    }

    public static function recruiterOptionsForAdmin(): array
    {
        return static::query()
            ->with('employerProfile:id,user_id,company_name')
            ->get(['id', 'name'])
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'employer_profile_id' => $user->employerProfile?->id,
            ])
            ->values()
            ->all();
    }
}
