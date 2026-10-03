<?php

namespace Database\Factories;

use App\Enums\RegistrationSource;
use App\Enums\StaffRoleSlug;
use App\Enums\UserRole;
use App\Models\CandidateProfile;
use App\Models\EmployerProfile;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'role' => UserRole::Admin,
            'registration_source' => RegistrationSource::Admin,
            'is_active' => true,
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (User $user) {
            if ($user->role !== UserRole::Admin) {
                return;
            }

            $role = Role::query()->where('slug', StaffRoleSlug::SuperAdmin->value)->first();
            if ($role && $user->roles()->doesntExist()) {
                $user->roles()->sync([$role->id]);
                $user->forgetPermissionCache();
            }
        });
    }

    public function candidate(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => UserRole::Candidate,
            'registration_source' => RegistrationSource::Website,
        ])->afterCreating(function (User $user) {
            CandidateProfile::query()->create(['user_id' => $user->id]);
            $user->forceFill(['profile_completion' => $user->calculateProfileCompletion()])->saveQuietly();
        });
    }

    public function employer(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => UserRole::Employer,
            'registration_source' => RegistrationSource::Website,
        ])->afterCreating(function (User $user) {
            EmployerProfile::query()->create(['user_id' => $user->id]);
            $user->forceFill(['profile_completion' => $user->calculateProfileCompletion()])->saveQuietly();
        });
    }

    public function partner(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => UserRole::Partner,
            'registration_source' => RegistrationSource::Admin,
        ]);
    }

    public function investor(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => UserRole::Investor,
            'registration_source' => RegistrationSource::Admin,
            'is_active' => false,
            'account_status' => \App\Enums\AccountStatus::PendingVerification,
        ]);
    }

    public function staffRole(string $slug): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => UserRole::Admin,
        ])->afterCreating(function (User $user) use ($slug) {
            $role = Role::query()->where('slug', $slug)->first();
            if ($role) {
                $user->roles()->sync([$role->id]);
                $user->forgetPermissionCache();
            }
        });
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
            'account_status' => \App\Enums\AccountStatus::Inactive,
        ]);
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
