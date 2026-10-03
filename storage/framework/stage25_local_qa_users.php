<?php

use App\Enums\AccountStatus;
use App\Enums\StaffRoleSlug;
use App\Enums\UserRole;
use App\Models\CandidateProfile;
use App\Models\Role;
use App\Models\User;
use App\Services\AuthorizationBootstrapper;
use Illuminate\Support\Facades\Hash;

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

app(AuthorizationBootstrapper::class)->run();

$admin = User::query()->updateOrCreate(
    ['email' => 'stage25-qa-admin@ournear.local'],
    [
        'name' => 'Stage 25 QA Admin',
        'password' => Hash::make('password'),
        'role' => UserRole::Admin,
        'is_active' => true,
        'must_change_password' => false,
        'account_status' => AccountStatus::Active,
        'email_verified_at' => now(),
    ],
);
$admin->forceFill(['must_change_password' => false, 'is_active' => true])->save();
$super = Role::query()->where('slug', StaffRoleSlug::SuperAdmin->value)->firstOrFail();
$admin->roles()->sync([$super->id]);
$admin->forgetPermissionCache();

$finance = User::query()->updateOrCreate(
    ['email' => 'stage25-qa-finance@ournear.local'],
    [
        'name' => 'Stage 25 QA Finance',
        'password' => Hash::make('password'),
        'role' => UserRole::Admin,
        'is_active' => true,
        'must_change_password' => false,
        'account_status' => AccountStatus::Active,
        'email_verified_at' => now(),
    ],
);
$financeRole = Role::query()->where('slug', StaffRoleSlug::Finance->value)->firstOrFail();
$finance->roles()->sync([$financeRole->id]);
$finance->forceFill(['must_change_password' => false, 'is_active' => true])->save();
$finance->forgetPermissionCache();

$candidate = User::query()->updateOrCreate(
    ['email' => 'stage25-qa-candidate@ournear.local'],
    [
        'name' => 'Stage 25 QA Candidate',
        'username' => 'stage25qacandidate',
        'password' => Hash::make('Password1!'),
        'role' => UserRole::Candidate,
        'is_active' => true,
        'must_change_password' => false,
        'account_status' => AccountStatus::Active,
        'email_verified_at' => now(),
        'registration_source' => \App\Enums\RegistrationSource::Website,
    ],
);
if ($candidate->candidateProfile === null) {
    CandidateProfile::query()->create(['user_id' => $candidate->id]);
}

echo "ready\n";
