<?php

declare(strict_types=1);

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Job;
use App\Models\Qualification;
use App\Models\Skill;
use App\Models\User;
use App\Support\QaHiringFixtures;

foreach ([QaHiringFixtures::EMPLOYER_A_EMAIL, QaHiringFixtures::EMPLOYER_B_EMAIL] as $email) {
    $user = User::query()->where('email', $email)->first();
    $profile = $user?->employerProfile;
    $verified = $profile?->ournearVerified() ? 'yes' : 'no';
    echo $email.' job_posting_approved_at='.($profile?->job_posting_approved_at ?? 'null')." ournear_verified={$verified}\n";
}

$published = Job::query()->where('slug', QaHiringFixtures::SLUG_PUBLISHED_A)->first();
if ($published) {
    echo 'slug_published_a='.$published->slug.' employer_id='.$published->employer_id."\n";
}

$pivotQual = \Illuminate\Support\Facades\Schema::hasTable('job_listing_qualification') ? 'yes' : 'no';
$pivotSkill = \Illuminate\Support\Facades\Schema::hasTable('job_listing_skill') ? 'yes' : 'no';
echo "pivot_qualification_table={$pivotQual}\n";
echo "pivot_skill_table={$pivotSkill}\n";

if ($pivotQual === 'yes' && $pivotSkill === 'yes') {
    $withReq = Job::query()
        ->where(function ($q) {
            $q->whereHas('qualifications')->orWhereHas('skills');
        })
        ->publiclyVisible()
        ->first();

    echo 'job_with_requirements_slug='.($withReq?->slug ?? 'none')."\n";
} else {
    echo "job_with_requirements_slug=skipped_pivot_missing\n";
}

echo 'qualification_count='.Qualification::query()->where('is_active', true)->count()."\n";
echo 'skill_count='.Skill::query()->count()."\n";

$verifiedCount = \App\Models\EmployerProfile::query()->whereNotNull('job_posting_approved_at')->count();
echo "verified_employer_profiles={$verifiedCount}\n";

exit(0);
