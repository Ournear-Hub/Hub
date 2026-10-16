<?php

declare(strict_types=1);

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Application;
use App\Models\Job;
use App\Models\User;
use App\Support\QaHiringFixtures;

$candidate = User::query()->where('email', QaHiringFixtures::CANDIDATE_EMAIL)->firstOrFail();
$employerA = User::query()->where('email', QaHiringFixtures::EMPLOYER_A_EMAIL)->firstOrFail();

$appliedJobIds = Application::query()
    ->where('user_id', $candidate->id)
    ->pluck('job_id')
    ->all();

echo 'candidate_id='.$candidate->id.PHP_EOL;
echo 'applied_job_ids='.json_encode($appliedJobIds).PHP_EOL;

$jobs = Job::query()
    ->whereHas('employer.user', fn ($q) => $q->where('id', $employerA->id))
    ->where('active', true)
    ->orderBy('id')
    ->get(['id', 'slug', 'title', 'business_id', 'publishing_status']);

echo PHP_EOL.'--- all published active jobs candidate has NOT applied to ---'.PHP_EOL;

$open = Job::query()
    ->where('active', true)
    ->where('publishing_status', 'published')
    ->whereNotIn('id', $appliedJobIds)
    ->with('employer.user:id,email')
    ->orderBy('id')
    ->limit(20)
    ->get(['id', 'slug', 'title', 'business_id', 'employer_id']);

foreach ($open as $job) {
    echo sprintf(
        "job id=%d slug=%s employer=%s title=%s\n",
        $job->id,
        $job->slug,
        $job->employer?->user?->email ?? 'null',
        $job->title,
    );
}

foreach ($jobs as $job) {
    $already = in_array((int) $job->id, array_map('intval', $appliedJobIds), true);
    echo sprintf(
        "job id=%d business_id=%s slug=%s publishing=%s already_applied=%s title=%s\n",
        $job->id,
        $job->business_id,
        $job->slug,
        $job->publishing_status?->value ?? 'null',
        $already ? 'yes' : 'no',
        $job->title,
    );
}

$resume = $candidate->candidateProfile?->resumes()->where('status', 'active')->first();
if ($resume) {
    echo 'library_resume_id='.$resume->id.' path='.$resume->storage_path.' size='.$resume->file_size.PHP_EOL;
}
