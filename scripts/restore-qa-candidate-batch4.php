<?php

declare(strict_types=1);

/**
 * Restores qa-candidate@example.test profile fields touched during Batch 4 browser audit.
 * Pre-audit UI baseline: location "QA Final E2E City", empty bio/headline/professional/skills/experience.
 */

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;

$user = User::query()->where('email', 'qa-candidate@example.test')->first();
if ($user === null) {
    echo "candidate not found\n";
    exit(1);
}

$profile = $user->candidateProfile;
if ($profile === null) {
    echo "candidate profile not found\n";
    exit(1);
}

$user->update([
    'location' => 'QA Final E2E City',
    'bio' => null,
    'professional_headline' => null,
]);

$profile->update([
    'location' => 'QA Final E2E City',
    'bio' => null,
    'headline' => null,
    'current_designation' => null,
    'current_company' => null,
    'industry_id' => null,
    'job_category_id' => null,
    'experience' => null,
    'qualification' => null,
    'qualification_notes' => null,
    'experience_notes' => null,
    'notice_period' => null,
    'current_salary' => null,
    'expected_salary' => null,
]);

$profile->skills()->whereIn('name', ['Batch4 Selenium', 'Batch5 UX Test'])->delete();
$profile->employmentHistories()->where('company', 'Batch4 Prior Co')->delete();
$profile->educations()->where('institution', 'Batch5 Temp College')->delete();
$profile->languages()->where('name', 'English')->where('proficiency', 'professional')->delete();
$profile->preferredLocations()->sync([]);
$profile->preferredEmploymentTypes()->sync([]);
$profile->update([
    'availability' => null,
    'employment_preference' => null,
    'willing_to_relocate' => false,
]);

echo "restored qa-candidate batch4 profile fields\n";
