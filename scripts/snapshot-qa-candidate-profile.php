<?php

declare(strict_types=1);

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;

$user = User::query()->where('email', 'qa-candidate@example.test')->first();
if ($user === null) {
    echo "candidate not found\n";
    exit(1);
}

$user->load([
    'candidateProfile.educations',
    'candidateProfile.skills',
    'candidateProfile.employmentHistories',
    'candidateProfile.languages',
    'candidateProfile.certifications',
    'candidateProfile.projects',
    'candidateProfile.preferredLocations',
    'candidateProfile.preferredEmploymentTypes',
    'candidateProfile.resumes',
]);

$p = $user->candidateProfile;
echo json_encode([
    'user' => [
        'name' => $user->name,
        'username' => $user->username,
        'email' => $user->email,
        'mobile' => $user->mobile,
    ],
    'profile' => $p ? $p->only([
        'business_id', 'bio', 'headline', 'location', 'location_id', 'mobile',
        'current_designation', 'current_company', 'industry_id', 'job_category_id',
        'experience', 'qualification', 'notice_period', 'current_salary', 'expected_salary',
    ]) : null,
    'educations_count' => $p?->educations->count(),
    'educations' => $p?->educations->map->only(['id', 'institution', 'degree', 'field_of_study'])->values(),
    'skills' => $p?->skills->pluck('name'),
    'employment_histories' => $p?->employmentHistories->map->only(['id', 'company', 'designation', 'started_on', 'ended_on', 'is_current']),
    'languages' => $p?->languages->map->only(['id', 'language', 'proficiency']),
    'certifications_count' => $p?->certifications->count(),
    'projects_count' => $p?->projects->count(),
    'preferred_location_ids' => $p?->preferredLocations->pluck('id'),
    'preferred_employment_type_ids' => $p?->preferredEmploymentTypes->pluck('id'),
], JSON_PRETTY_PRINT).PHP_EOL;
