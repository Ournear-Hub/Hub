<?php

namespace App\Console\Commands;

use App\Support\QaHiringFixtures;
use Illuminate\Console\Command;

class QaHiringFixturesCommand extends Command
{
    protected $signature = 'qa:hiring-fixtures';

    protected $description = 'Provision deterministic local/testing hiring QA accounts, jobs, and one application (local/testing only)';

    public function handle(QaHiringFixtures $fixtures): int
    {
        if ($this->laravel->isProduction()) {
            $this->error('qa:hiring-fixtures cannot run when APP_ENV=production.');

            return self::FAILURE;
        }

        if (! $this->laravel->environment(['local', 'testing'])) {
            $this->error('qa:hiring-fixtures is restricted to local and testing environments only.');

            return self::FAILURE;
        }

        $result = $fixtures->provision();

        $this->line('');
        $this->info('HIRING QA FIXTURES READY');
        $this->line('');

        $employerA = $result['employer_a'];
        $employerB = $result['employer_b'];
        $candidate = $result['candidate'];
        $publishedA = $result['published_job_a'];
        $draftA = $result['draft_job_a'];
        $publishedB = $result['published_job_b'];
        $application = $result['application'];

        $this->line('Employer A:');
        $this->line('  email: '.$employerA->email);
        $this->line('  password: '.QaHiringFixtures::PASSWORD);
        $this->line('  company: '.$employerA->employerProfile?->company_name);
        $this->line('  published job: '.$publishedA->title.' ('.$publishedA->slug.')');
        $this->line('  draft job: '.$draftA->title.' ('.$draftA->slug.')');
        $this->line('');

        $this->line('Employer B:');
        $this->line('  email: '.$employerB->email);
        $this->line('  password: '.QaHiringFixtures::PASSWORD);
        $this->line('  company: '.$employerB->employerProfile?->company_name);
        $this->line('  published job: '.$publishedB->title.' ('.$publishedB->slug.')');
        $this->line('');

        $this->line('Candidate:');
        $this->line('  email: '.$candidate->email);
        $this->line('  password: '.QaHiringFixtures::PASSWORD);
        $this->line('');

        $this->line('Application:');
        $this->line('  candidate: '.$candidate->email);
        $this->line('  job: '.$publishedA->title);
        $this->line('  employer: '.$employerA->email);
        $this->line('  status: '.$application->status->value);
        $this->line('');

        $this->line('Notification:');
        $this->line('  created: '.($result['notification_created'] ? 'yes' : 'no'));
        $this->line('  recipient: '.$result['notification_recipient']);
        $this->line('');

        return self::SUCCESS;
    }
}
