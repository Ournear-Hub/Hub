<?php

declare(strict_types=1);

/**
 * Read-only: first selectable job master-data chain for QA browser job forms.
 * Exit 0 when found, 2 when prerequisites missing.
 */

use App\Models\EmploymentType;
use App\Models\ExperienceRange;
use App\Models\JobTitle;
use App\Models\Location;
use App\Models\User;
use App\Support\QaHiringFixtures;

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$employer = User::query()->where('email', QaHiringFixtures::EMPLOYER_A_EMAIL)->first();
if (! $employer?->employerProfile) {
    fwrite(STDERR, "Run php artisan qa:hiring-fixtures first.\n");
    exit(2);
}

$title = JobTitle::query()
    ->with('category.industry')
    ->where('is_active', true)
    ->whereHas('category', fn ($q) => $q->where('is_active', true)->whereHas('industry', fn ($iq) => $iq->where('is_active', true)))
    ->first();

if (! $title?->category?->industry) {
    fwrite(STDERR, "No active industry/category/job_title chain in database.\n");
    exit(2);
}

$location = Location::query()->where('is_active', true)->orderBy('sort_order')->first();
$employment = EmploymentType::query()->where('is_active', true)->orderBy('sort_order')->first();
$experience = ExperienceRange::query()->where('is_active', true)->orderBy('sort_order')->first();

if (! $location || ! $employment || ! $experience) {
    fwrite(STDERR, "Missing active location, employment type, or experience range.\n");
    exit(2);
}

echo "found=1\n";
echo 'employer_email='.QaHiringFixtures::EMPLOYER_A_EMAIL."\n";
echo 'company_name='.str_replace(["\n", "\r"], '', (string) $employer->employerProfile->displayCompany())."\n";
echo 'industry_id='.$title->category->industry_id."\n";
echo 'industry_name='.str_replace(["\n", "\r"], '', (string) $title->category->industry->name)."\n";
echo 'job_category_id='.$title->job_category_id."\n";
echo 'job_category_name='.str_replace(["\n", "\r"], '', (string) $title->category->name)."\n";
echo 'job_title_id='.$title->id."\n";
echo 'job_title_name='.str_replace(["\n", "\r"], '', (string) $title->name)."\n";
echo 'location_id='.$location->id."\n";
echo 'location_name='.str_replace(["\n", "\r"], '', (string) $location->name)."\n";
echo 'employment_type_id='.$employment->id."\n";
echo 'employment_type_name='.str_replace(["\n", "\r"], '', (string) $employment->name)."\n";
echo 'experience_range_id='.$experience->id."\n";
echo 'experience_range_name='.str_replace(["\n", "\r"], '', (string) $experience->name)."\n";

exit(0);
