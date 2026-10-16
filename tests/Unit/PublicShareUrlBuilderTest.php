<?php

namespace Tests\Unit;

use App\Models\EmployerProfile;
use App\Models\Job;
use App\Models\User;
use App\Support\PublicShareMessageBuilder;
use App\Support\PublicShareUrlBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicShareUrlBuilderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['sharing.public_base_url' => 'https://ournear.app']);
    }

    public function test_job_share_url_uses_public_domain_and_job_slug(): void
    {
        $employer = $this->makeEmployer();
        $job = Job::factory()->create([
            'employer_id' => $employer->employerProfile->id,
            'slug' => 'mining-engineer-open-cast',
        ]);

        $url = app(PublicShareUrlBuilder::class)->forJob($job);

        $this->assertSame('https://ournear.app/jobs/mining-engineer-open-cast', $url);
    }

    public function test_company_share_url_uses_employer_profile_id_route(): void
    {
        $employer = $this->makeEmployer();
        $profile = $employer->employerProfile;
        $profile->update(['company_name' => 'Acme Mining']);
        $profile->forceFill(['public_share' => ['enabled' => true]])->save();

        $url = app(PublicShareUrlBuilder::class)->forCompany($profile);

        $this->assertSame('https://ournear.app/companies/'.$profile->id, $url);
    }

    public function test_profile_share_url_uses_username_route(): void
    {
        $candidate = $this->makeCandidate(['username' => 'jane-miner']);
        $candidate->candidateProfile?->update(['public_share' => ['enabled' => true]]);

        $url = app(PublicShareUrlBuilder::class)->forProfile($candidate);

        $this->assertSame('https://ournear.app/profiles/jane-miner', $url);
    }

    public function test_whatsapp_message_includes_job_title_and_url_with_special_characters(): void
    {
        $messages = app(PublicShareMessageBuilder::class);
        $text = $messages->whatsappText('job', [
            'title' => 'Mining Engineer — Open Cast',
            'company' => 'Acme & Co',
            'location' => 'Perth, WA',
            'url' => 'https://ournear.app/jobs/mining-engineer',
        ]);

        $this->assertStringContainsString('Mining Engineer — Open Cast', $text);
        $this->assertStringContainsString('https://ournear.app/jobs/mining-engineer', $text);
        $this->assertStringContainsString('Acme & Co', $text);
    }

    public function test_email_subject_differs_between_job_and_company(): void
    {
        $messages = app(PublicShareMessageBuilder::class);

        $jobMail = $messages->email('job', [
            'title' => 'Welder',
            'url' => 'https://ournear.app/jobs/welder',
        ]);
        $companyMail = $messages->email('company', [
            'title' => 'Steel Works',
            'url' => 'https://ournear.app/companies/12',
        ]);

        $this->assertSame('Job opportunity on OurNear Hire', $jobMail['subject']);
        $this->assertStringContainsString('Welder', $jobMail['body']);
        $this->assertSame('Steel Works on OurNear Hire', $companyMail['subject']);
        $this->assertStringNotContainsString('Job opportunity', $companyMail['subject']);
    }

    public function test_public_job_card_includes_share_url(): void
    {
        config(['sharing.public_base_url' => 'https://ournear.app']);
        $employer = $this->makeEmployer();
        $job = Job::factory()->create([
            'employer_id' => $employer->employerProfile->id,
            'slug' => 'share-card-role',
        ]);

        $card = $job->fresh()->toPublicCard();

        $this->assertSame('https://ournear.app/jobs/share-card-role', $card['share_url']);
    }
}
