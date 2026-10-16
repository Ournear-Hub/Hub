<?php

namespace Tests\Feature;

use App\Enums\CandidateInterviewResponse;
use App\Enums\CommunicationChannel;
use App\Enums\SystemMessageTemplateKey;
use App\Mail\EmployerRecruitmentMail;
use App\Models\Application;
use App\Models\Job;
use App\Models\MessageTemplate;
use App\Services\CommunicationPreferenceService;
use App\Services\EmployerNotificationService;
use App\Services\MessageTemplateService;
use App\Support\MessageTemplateVariableCatalog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class EmployerMessageTemplateTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        app(MessageTemplateService::class)->syncSystemTemplates();
    }

    public function test_employer_application_received_uses_registry_body(): void
    {
        $employer = $this->makeEmployer();
        $job = Job::factory()->create(['employer_id' => $employer->employerProfile?->id, 'title' => 'Welder']);
        $application = Application::factory()->create([
            'job_id' => $job->id,
            'full_name' => 'Asha Candidate',
        ]);

        $template = MessageTemplate::query()
            ->where('template_key', SystemMessageTemplateKey::EmployerApplicationReceivedInApp->value)
            ->firstOrFail();

        $admin = $this->makeAdmin();
        $this->actingAs($admin)
            ->put(route('admin.message-templates.update', $template), [
                'name' => $template->name,
                'description' => $template->description,
                'channel' => $template->channel->value,
                'audience' => $template->audience->value,
                'body' => 'Registry copy: {{candidate_name}} for {{job_title}} at {{company_name}}.',
                'is_active' => true,
            ])
            ->assertRedirect();

        $rendered = app(MessageTemplateService::class)->renderForApplication(
            SystemMessageTemplateKey::EmployerApplicationReceivedInApp->value,
            $application,
        );

        $this->assertTrue($rendered['used_template']);
        $this->assertStringContainsString('Asha Candidate', (string) $rendered['body']);
        $this->assertStringContainsString('Welder', (string) $rendered['body']);
    }

    public function test_inactive_employer_template_falls_back_without_cross_company_leakage(): void
    {
        $employerA = $this->makeEmployer(['email' => 'employer-a@example.com']);
        $employerB = $this->makeEmployer(['email' => 'employer-b@example.com']);
        $jobA = Job::factory()->create(['employer_id' => $employerA->employerProfile?->id, 'title' => 'Role A']);
        $jobB = Job::factory()->create(['employer_id' => $employerB->employerProfile?->id, 'title' => 'Role B Secret']);
        $applicationA = Application::factory()->create(['job_id' => $jobA->id, 'full_name' => 'Candidate A']);
        Application::factory()->create(['job_id' => $jobB->id, 'full_name' => 'Candidate B']);

        $template = MessageTemplate::query()
            ->where('template_key', SystemMessageTemplateKey::EmployerApplicationReceivedInApp->value)
            ->firstOrFail();
        $template->update([
            'is_active' => false,
            'body' => 'Should never render {{job_title}}',
        ]);

        app(EmployerNotificationService::class)->notifyApplicationReceived($applicationA);

        $rendered = app(MessageTemplateService::class)->renderForApplication(
            SystemMessageTemplateKey::EmployerApplicationReceivedInApp->value,
            $applicationA,
            null,
            null,
            'Fallback for {{job_title}}.',
        );

        $this->assertFalse($rendered['used_template']);
        $this->assertStringContainsString('Role A', (string) $rendered['body']);
        $this->assertStringNotContainsString('Role B Secret', (string) $rendered['body']);
        $this->assertSame(0, $employerB->fresh()->notifications()->count());
    }

    public function test_unsupported_variable_rejected_for_employer_template(): void
    {
        $admin = $this->makeAdmin();
        $template = MessageTemplate::query()
            ->where('template_key', SystemMessageTemplateKey::EmployerInterviewRespondedInApp->value)
            ->firstOrFail();

        $this->actingAs($admin)
            ->from(route('admin.message-templates.index'))
            ->put(route('admin.message-templates.update', $template), [
                'name' => $template->name,
                'description' => $template->description,
                'channel' => $template->channel->value,
                'audience' => $template->audience->value,
                'body' => 'Bad {{secret_token}}',
                'is_active' => true,
            ])
            ->assertSessionHasErrors('body');
    }

    public function test_interview_response_variables_render_from_application_context(): void
    {
        $employer = $this->makeEmployer();
        $job = Job::factory()->create(['employer_id' => $employer->employerProfile?->id]);
        $application = Application::factory()->create([
            'job_id' => $job->id,
            'full_name' => 'Sam Candidate',
        ]);
        $variables = array_merge(
            MessageTemplateVariableCatalog::forApplication($application),
            [
                'interview_response_label' => CandidateInterviewResponse::Accepted->label(),
                'interview_number' => '2',
            ],
        );

        $body = app(MessageTemplateService::class)->renderString(
            '{{candidate_name}} {{interview_response_label}} #{{interview_number}}',
            $variables,
            MessageTemplateVariableCatalog::allowedForKey(
                SystemMessageTemplateKey::EmployerInterviewRespondedInApp->value,
            ),
            SystemMessageTemplateKey::EmployerInterviewRespondedInApp->channel(),
        );

        $this->assertStringContainsString('Sam Candidate', $body);
        $this->assertStringContainsString(CandidateInterviewResponse::Accepted->label(), $body);
        $this->assertStringContainsString('#2', $body);
    }

    public function test_employer_email_system_templates_are_registered(): void
    {
        $keys = [
            SystemMessageTemplateKey::EmployerApplicationReceivedEmail,
            SystemMessageTemplateKey::EmployerInterviewRespondedEmail,
            SystemMessageTemplateKey::EmployerApplicationMessageEmail,
            SystemMessageTemplateKey::EmployerCorrectionRequestEmail,
        ];

        foreach ($keys as $key) {
            $template = MessageTemplate::query()->where('template_key', $key->value)->first();
            $this->assertNotNull($template, $key->value);
            $this->assertTrue($template->is_active);
            $this->assertSame($key->channel()->value, $template->channel->value);
            $this->assertSame($key->audience()->value, $template->audience->value);
        }
    }

    public function test_application_received_queues_employer_email_with_edited_template(): void
    {
        Mail::fake();
        $admin = $this->makeAdmin();
        $employer = $this->makeEmployer([
            'email' => 'employer-queue@example.com',
            'email_verified_at' => now(),
            'mobile_verified_at' => now(),
        ]);
        $candidate = $this->makeCandidate(['email_verified_at' => now(), 'mobile_verified_at' => now()]);
        $this->giveCandidateResume($candidate);
        $job = Job::factory()->create([
            'employer_id' => $employer->employerProfile?->id,
            'title' => 'Fabricator',
        ]);

        $template = MessageTemplate::query()
            ->where('template_key', SystemMessageTemplateKey::EmployerApplicationReceivedEmail->value)
            ->firstOrFail();

        $this->actingAs($admin)
            ->put(route('admin.message-templates.update', $template), [
                'name' => $template->name,
                'description' => $template->description,
                'channel' => $template->channel->value,
                'audience' => $template->audience->value,
                'subject' => 'Email: new applicant for {{job_title}}',
                'body' => 'Email body: {{candidate_name}} applied for {{job_title}}.',
                'is_active' => true,
            ])
            ->assertRedirect();

        $this->applyAsCandidate($candidate, $job);

        Mail::assertQueued(EmployerRecruitmentMail::class, function (EmployerRecruitmentMail $mail) {
            return $mail->templateKey === SystemMessageTemplateKey::EmployerApplicationReceivedEmail->value
                && $mail->envelope()->subject === 'Email: new applicant for Fabricator'
                && str_contains($mail->render(), 'Email body:')
                && str_contains($mail->render(), 'Fabricator');
        });

        Mail::assertQueued(EmployerRecruitmentMail::class, 1);
        $this->assertSame(1, $employer->fresh()->unreadNotifications()->count());
    }

    public function test_system_opt_out_skips_employer_recruitment_email_only(): void
    {
        Mail::fake();
        $employer = $this->makeEmployer([
            'email' => 'optout-employer@example.com',
            'email_verified_at' => now(),
            'mobile_verified_at' => now(),
        ]);
        app(CommunicationPreferenceService::class)->sync($employer, [
            CommunicationChannel::System->value => false,
        ], 'test');

        $job = Job::factory()->create(['employer_id' => $employer->employerProfile?->id]);
        $application = Application::factory()->create(['job_id' => $job->id, 'full_name' => 'Opt Out Test']);

        DB::transaction(function () use ($application) {
            app(EmployerNotificationService::class)->notifyApplicationReceived($application);
        });

        Mail::assertNothingQueued();
        $this->assertSame(1, $employer->fresh()->unreadNotifications()->count());
    }

    public function test_inactive_employer_email_template_uses_service_fallback_copy(): void
    {
        $employer = $this->makeEmployer();
        $job = Job::factory()->create(['employer_id' => $employer->employerProfile?->id, 'title' => 'Operator']);
        $application = Application::factory()->create([
            'job_id' => $job->id,
            'full_name' => 'Fallback Candidate',
        ]);

        $template = MessageTemplate::query()
            ->where('template_key', SystemMessageTemplateKey::EmployerApplicationReceivedEmail->value)
            ->firstOrFail();
        $template->update([
            'is_active' => false,
            'subject' => 'Hidden {{job_title}}',
            'body' => 'Hidden body {{candidate_name}}',
        ]);

        $rendered = app(MessageTemplateService::class)->renderForApplication(
            SystemMessageTemplateKey::EmployerApplicationReceivedEmail->value,
            $application,
            null,
            'New application received',
            'Fallback Candidate applied for Operator.',
        );

        $this->assertFalse($rendered['used_template']);
        $this->assertSame('New application received', $rendered['subject']);
        $this->assertStringContainsString('Operator', (string) $rendered['body']);
        $this->assertStringNotContainsString('Hidden', (string) $rendered['body']);
    }
}
