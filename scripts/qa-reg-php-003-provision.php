<?php

declare(strict_types=1);

/**
 * Local QA fixture for REG-PHP-003 browser acceptance (dedicated employer only).
 *
 * Usage:
 *   php scripts/qa-reg-php-003-provision.php prepare
 *   php scripts/qa-reg-php-003-provision.php expire <slug>
 */

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Enums\JobPublishingStatus;
use App\Enums\UserRole;
use App\Models\EmployerProfile;
use App\Models\EmploymentType;
use App\Models\ExperienceRange;
use App\Models\Job;
use App\Models\JobTitle;
use App\Models\Location;
use App\Models\User;
use App\Services\EmployerJobPublicationPolicy;
use App\Support\QaHiringFixtures;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

const QA_UNVERIFIED_PUBLISH_EMAIL = 'qa-unverified-publish@example.test';

function qa_unverified_employer(): User
{
    $user = User::query()->where('email', QA_UNVERIFIED_PUBLISH_EMAIL)->first();
    if ($user === null) {
        $user = User::factory()->employer()->create([
            'email' => QA_UNVERIFIED_PUBLISH_EMAIL,
            'username' => 'qaunverifiedpub',
            'name' => 'QA Unverified Publish',
            'password' => Hash::make(QaHiringFixtures::PASSWORD),
            'email_verified_at' => now(),
            'mobile_verified_at' => now(),
        ]);
    } else {
        $user->forceFill([
            'role' => UserRole::Employer,
            'email_verified_at' => $user->email_verified_at ?? now(),
            'mobile_verified_at' => $user->mobile_verified_at ?? now(),
            'password' => Hash::make(QaHiringFixtures::PASSWORD),
        ])->save();
    }

    $profile = $user->employerProfile;
    if ($profile === null) {
        $profile = EmployerProfile::query()->create([
            'user_id' => $user->id,
            'company_name' => 'QA Unverified Publish Co',
        ]);
    }

    $profile->forceFill([
        'job_posting_approved_at' => null,
        'job_posting_approved_by' => null,
        'trial_job_used_at' => null,
        'company_name' => 'QA Unverified Publish Co',
    ])->save();

    return $user->fresh(['employerProfile']);
}

function qa_master_chain(): array
{
    $title = JobTitle::query()
        ->with('category.industry')
        ->where('is_active', true)
        ->whereHas('category', fn ($q) => $q->where('is_active', true)->whereHas('industry', fn ($iq) => $iq->where('is_active', true)))
        ->first();

    $location = Location::query()->where('is_active', true)->orderBy('sort_order')->first();
    $employment = EmploymentType::query()->where('is_active', true)->orderBy('sort_order')->first();
    $experience = ExperienceRange::query()->where('is_active', true)->orderBy('sort_order')->first();

    if ($title === null || $location === null || $employment === null || $experience === null) {
        fwrite(STDERR, "Missing master data for job creation.\n");
        exit(2);
    }

    return compact('title', 'location', 'employment', 'experience');
}

$action = $argv[1] ?? 'prepare';

if ($action === 'expire') {
    $slug = $argv[2] ?? 'qa-reg-php-003-trial';
    $job = Job::query()->where('slug', $slug)->first();
    if ($job === null) {
        fwrite(STDERR, "Job not found: {$slug}\n");
        exit(3);
    }
    $job->forceFill(['trial_expires_at' => now()->subMinutes(10)])->save();
    echo "expired_slug={$slug}\n";
    exit(0);
}

if ($action !== 'prepare') {
    fwrite(STDERR, "Unknown action: {$action}\n");
    exit(1);
}

$user = qa_unverified_employer();
$profile = $user->employerProfile;
if ($profile === null) {
    fwrite(STDERR, "Employer profile missing.\n");
    exit(2);
}

Job::query()->where('employer_id', $profile->id)->delete();

$chain = qa_master_chain();
/** @var JobTitle $jobTitle */
$jobTitle = $chain['title'];

$policy = app(EmployerJobPublicationPolicy::class);
$package = $policy->packageForPublish($profile);

$title = 'REG-PHP-003 Trial Role';
$slug = 'qa-reg-php-003-trial';

$job = Job::factory()->create([
    'employer_id' => $profile->id,
    'title' => $title,
    'slug' => $slug,
    'project' => $profile->displayCompany(),
    'industry_id' => $jobTitle->category->industry_id,
    'job_category_id' => $jobTitle->job_category_id,
    'job_title_id' => $jobTitle->id,
    'location_id' => $chain['location']->id,
    'employment_type_id' => $chain['employment']->id,
    'experience_range_id' => $chain['experience']->id,
    'publishing_status' => JobPublishingStatus::Published,
    'active' => true,
    'featured' => false,
    'posting_tier' => $package['posting_tier'],
    'trial_expires_at' => $package['trial_expires_at'],
    'posted_date' => now()->toDateString(),
    'closing_date' => now()->addDays(30)->toDateString(),
]);

if ($package['mark_trial_used']) {
    $profile->forceFill(['trial_job_used_at' => now()])->save();
}

echo 'email='.QA_UNVERIFIED_PUBLISH_EMAIL."\n";
echo 'password='.QaHiringFixtures::PASSWORD."\n";
echo 'slug='.$job->slug."\n";
echo 'title='.str_replace(["\n", "\r"], '', $title)."\n";
echo 'trial_expires_at='.($job->trial_expires_at?->toIso8601String() ?? 'null')."\n";
echo 'ournear_verified='.($profile->fresh()->ournearVerified() ? 'yes' : 'no')."\n";

exit(0);
