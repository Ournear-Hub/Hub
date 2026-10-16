<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Enums\MessageTemplateChannel;
use App\Enums\SystemMessageTemplateKey;
use App\Mail\ApplicationRejectedMail;
use App\Models\Application;
use App\Models\Job;
use App\Models\MessageTemplate;
use App\Models\MessageTemplateAudit;
use App\Models\User;
use App\Services\CandidateNotificationService;
use App\Services\MessageTemplateService;
use App\Support\MessageTemplateRenderer;
use Illuminate\Support\Facades\Mail;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class MessageTemplateManagementTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        app(MessageTemplateService::class)->syncSystemTemplates();
    }

    public function test_admin_can_list_message_templates(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->get(route('admin.message-templates.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/MessageTemplates/Index')
                ->has('templates', count(SystemMessageTemplateKey::cases())));
    }

    public function test_staff_without_manage_permission_cannot_create_templates(): void
    {
        $staff = $this->makeStaff('recruiter');

        $this->actingAs($staff)
            ->get(route('admin.message-templates.index'))
            ->assertOk();

        $this->actingAs($staff)
            ->post(route('admin.message-templates.store'), [
                'template_key' => 'recruitment.custom.in_app',
                'name' => 'Custom',
                'channel' => MessageTemplateChannel::InApp->value,
                'audience' => 'candidate',
                'body' => 'Hello {{candidate_name}}',
            ])
            ->assertForbidden();
    }

    public function test_duplicate_template_key_is_rejected(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->post(route('admin.message-templates.store'), [
                'template_key' => 'recruitment.custom.in_app',
                'name' => 'Custom one',
                'channel' => MessageTemplateChannel::InApp->value,
                'audience' => 'candidate',
                'body' => 'Hello {{candidate_name}}',
            ])
            ->assertRedirect();

        $this->actingAs($admin)
            ->from(route('admin.message-templates.index'))
            ->post(route('admin.message-templates.store'), [
                'template_key' => 'recruitment.custom.in_app',
                'name' => 'Custom two',
                'channel' => MessageTemplateChannel::InApp->value,
                'audience' => 'candidate',
                'body' => 'Hello {{candidate_name}}',
            ])
            ->assertSessionHasErrors('template_key');
    }

    public function test_unsupported_placeholder_is_rejected_on_update(): void
    {
        $admin = $this->makeAdmin();
        $template = MessageTemplate::query()
            ->where('template_key', SystemMessageTemplateKey::ApplicationRejectedInApp->value)
            ->firstOrFail();

        $this->actingAs($admin)
            ->from(route('admin.message-templates.index'))
            ->put(route('admin.message-templates.update', $template), [
                'name' => $template->name,
                'description' => $template->description,
                'channel' => $template->channel->value,
                'audience' => $template->audience->value,
                'body' => 'Bad {{secret_token}} reference.',
                'is_active' => true,
            ])
            ->assertSessionHasErrors('body');
    }

    public function test_renderer_escapes_email_html_and_ignores_unknown_variables(): void
    {
        $renderer = app(MessageTemplateRenderer::class);
        $rendered = $renderer->render(
            'Hello {{candidate_name}} {{unknown_var}}',
            ['candidate_name' => '<script>alert(1)</script>'],
            ['candidate_name'],
            MessageTemplateChannel::Email,
        );

        $this->assertStringContainsString('&lt;script&gt;', $rendered);
        $this->assertStringNotContainsString('unknown_var', $rendered);
    }

    public function test_preview_does_not_send_mail_or_change_application(): void
    {
        Mail::fake();
        $admin = $this->makeAdmin();
        $application = Application::factory()->create();

        $this->actingAs($admin)
            ->postJson(route('admin.message-templates.preview'), [
                'channel' => MessageTemplateChannel::InApp->value,
                'body' => 'Hello {{candidate_name}} for {{job_title}}',
            ])
            ->assertOk()
            ->assertJsonPath('body', 'Hello Sample Candidate for Site Engineer');

        Mail::assertNothingSent();
        $this->assertSame($application->fresh()->status, $application->status);
    }

    public function test_system_required_template_cannot_be_deactivated(): void
    {
        $admin = $this->makeAdmin();
        $template = MessageTemplate::query()
            ->where('template_key', SystemMessageTemplateKey::ApplicationRejectedEmail->value)
            ->firstOrFail();

        $this->actingAs($admin)
            ->from(route('admin.message-templates.index'))
            ->post(route('admin.message-templates.active', $template), ['is_active' => false])
            ->assertSessionHasErrors('is_active');

        $this->assertTrue($template->fresh()->is_active);
    }

    public function test_template_update_writes_audit_record(): void
    {
        $admin = $this->makeAdmin();
        $template = MessageTemplate::query()
            ->where('template_key', SystemMessageTemplateKey::ApplicationRejectedInApp->value)
            ->firstOrFail();

        $this->actingAs($admin)
            ->put(route('admin.message-templates.update', $template), [
                'name' => $template->name,
                'description' => $template->description,
                'channel' => $template->channel->value,
                'audience' => $template->audience->value,
                'body' => 'Updated copy for {{job_title}}.',
                'is_active' => true,
            ])
            ->assertRedirect();

        $this->assertTrue(
            MessageTemplateAudit::query()
                ->where('message_template_id', $template->id)
                ->where('action', 'updated')
                ->exists()
        );
    }

    public function test_rejected_email_uses_edited_template_subject(): void
    {
        Mail::fake();
        $admin = $this->makeAdmin();
        $job = Job::factory()->create(['title' => 'Electrician']);
        $candidate = $this->makeCandidate(['email' => 'candidate@example.com']);
        $application = Application::factory()->create([
            'job_id' => $job->id,
            'user_id' => $candidate->id,
            'email' => 'candidate@example.com',
            'status' => ApplicationStatus::Shortlisted,
        ]);

        $template = MessageTemplate::query()
            ->where('template_key', SystemMessageTemplateKey::ApplicationRejectedEmail->value)
            ->firstOrFail();

        $this->actingAs($admin)
            ->put(route('admin.message-templates.update', $template), [
                'name' => $template->name,
                'description' => $template->description,
                'channel' => $template->channel->value,
                'audience' => $template->audience->value,
                'subject' => 'Custom rejection subject for {{job_title}}',
                'body' => 'Custom body for {{candidate_name}}.',
                'is_active' => true,
            ])
            ->assertRedirect();

        $this->actingAs($admin)
            ->patch(route('admin.applications.status', $application), [
                'status' => ApplicationStatus::Rejected->value,
            ])
            ->assertRedirect();

        Mail::assertQueued(ApplicationRejectedMail::class, function (ApplicationRejectedMail $mail) {
            return $mail->envelope()->subject === 'Custom rejection subject for Electrician';
        });
    }

    public function test_inactive_template_falls_back_to_safe_copy(): void
    {
        $job = Job::factory()->create(['title' => 'Welder']);
        $application = Application::factory()->create([
            'job_id' => $job->id,
            'status' => ApplicationStatus::New,
        ]);

        $template = MessageTemplate::query()
            ->where('template_key', SystemMessageTemplateKey::ApplicationReceivedInApp->value)
            ->firstOrFail();
        $template->update(['is_active' => false, 'body' => 'Should not appear {{job_title}}']);

        $rendered = app(MessageTemplateService::class)->renderForApplication(
            SystemMessageTemplateKey::ApplicationReceivedInApp->value,
            $application,
            null,
            null,
            'Fallback copy for {{job_title}}.',
        );

        $this->assertFalse($rendered['used_template']);
        $this->assertStringContainsString('Welder', (string) $rendered['body']);
        $this->assertStringNotContainsString('Should not appear', (string) $rendered['body']);
    }

}
