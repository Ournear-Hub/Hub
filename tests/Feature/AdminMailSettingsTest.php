<?php

namespace Tests\Feature;

use App\Mail\AdminMailDiagnosticTestMail;
use App\Models\WebsiteSetting;
use App\Services\MailConfigurationService;
use Illuminate\Support\Facades\Mail;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminMailSettingsTest extends TestCase
{
    public function test_authorized_admin_can_update_mail_settings_and_password_is_encrypted(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->put(route('admin.settings.email'), [
                'mail_mailer' => 'smtp',
                'mail_enabled' => true,
                'smtp_host' => 'smtp-relay.brevo.com',
                'smtp_port' => 587,
                'smtp_encryption' => 'tls',
                'smtp_username' => 'login@example.test',
                'smtp_password' => 'secret-smtp-key',
                'smtp_timeout' => 20,
                'mail_use_queue' => true,
            ])
            ->assertRedirect(route('admin.settings.edit', ['tab' => 'email']));

        $row = WebsiteSetting::query()->where('lock_key', 'default')->first();
        $this->assertNotNull($row);
        $this->assertSame('smtp-relay.brevo.com', $row->smtp_host);
        $this->assertNotSame('secret-smtp-key', (string) $row->getAttributes()['smtp_password']);
        $this->assertSame('secret-smtp-key', $row->smtp_password);

        $this->assertSame('smtp-relay.brevo.com', config('mail.mailers.smtp.host'));
    }

    public function test_blank_password_preserves_existing_credential(): void
    {
        $admin = $this->makeAdmin();
        $row = WebsiteSetting::query()->where('lock_key', 'default')->first()
            ?? WebsiteSetting::query()->create(app(\App\Services\WebsiteSettingService::class)->defaults());

        $row->forceFill([
            'mail_enabled' => true,
            'mail_mailer' => 'smtp',
            'smtp_host' => 'smtp.example.test',
            'smtp_port' => 587,
            'smtp_username' => 'user',
            'smtp_password' => 'keep-me',
        ])->save();

        $this->actingAs($admin)
            ->put(route('admin.settings.email'), [
                'mail_mailer' => 'smtp',
                'mail_enabled' => true,
                'smtp_host' => 'smtp.example.test',
                'smtp_port' => 587,
                'smtp_encryption' => 'tls',
                'smtp_username' => 'user',
                'smtp_password' => '',
                'mail_use_queue' => true,
            ])
            ->assertRedirect();

        $this->assertSame('keep-me', $row->fresh()->smtp_password);
    }

    public function test_mail_secrets_are_not_exposed_in_inertia_props(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->put(route('admin.settings.email'), [
                'mail_mailer' => 'smtp',
                'mail_enabled' => true,
                'smtp_host' => 'smtp.example.test',
                'smtp_port' => 587,
                'smtp_encryption' => 'tls',
                'smtp_username' => 'user',
                'smtp_password' => 'hidden-value',
                'mail_use_queue' => true,
            ]);

        $this->actingAs($admin)
            ->get(route('admin.settings.edit', ['tab' => 'email']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Settings')
                ->where('mailSettings.smtp_password_configured', true)
                ->missing('mailSettings.smtp_password')
                ->missing('mailSettings.mail_password'));
    }

    public function test_unauthorized_user_cannot_update_mail_settings(): void
    {
        $this->actingAs($this->makeRecruiter())
            ->put(route('admin.settings.email'), [
                'mail_mailer' => 'smtp',
                'mail_enabled' => true,
            ])
            ->assertForbidden();
    }

    public function test_recruiter_cannot_use_mail_diagnostic_endpoints(): void
    {
        $recruiter = $this->makeRecruiter();

        $this->actingAs($recruiter)
            ->postJson(route('admin.settings.email.validate'))
            ->assertForbidden();

        $this->actingAs($recruiter)
            ->postJson(route('admin.settings.email.test-connection'))
            ->assertForbidden();

        $this->actingAs($recruiter)
            ->getJson(route('admin.settings.email.prerequisites'))
            ->assertForbidden();
    }

    public function test_invalid_smtp_port_is_rejected(): void
    {
        $this->actingAs($this->makeAdmin())
            ->put(route('admin.settings.email'), [
                'mail_mailer' => 'smtp',
                'mail_enabled' => true,
                'smtp_port' => 70000,
            ])
            ->assertSessionHasErrors('smtp_port');
    }

    public function test_send_test_email_uses_mail_fake(): void
    {
        Mail::fake();
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->put(route('admin.settings.email'), [
                'mail_mailer' => 'log',
                'mail_enabled' => true,
                'mail_use_queue' => false,
            ])
            ->assertRedirect();

        $response = $this->actingAs($admin)
            ->postJson(route('admin.settings.email.send-test'), [
                'recipient' => 'admin-diagnostic@example.test',
            ])
            ->assertOk();

        $outcome = (string) $response->json('outcome');
        $this->assertContains($outcome, ['transport_accepted', 'queued']);

        if ($outcome === 'queued') {
            Mail::assertQueued(AdminMailDiagnosticTestMail::class);
        } else {
            Mail::assertSent(AdminMailDiagnosticTestMail::class);
        }
    }

    public function test_validate_configuration_reports_missing_sender(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->postJson(route('admin.settings.email.validate'))
            ->assertOk()
            ->assertJsonPath('valid', false);
    }

    public function test_runtime_configuration_applies_website_url_for_links(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->put(route('admin.settings.website'), [
                'company_name' => 'OurNear',
                'office_phone' => '',
                'office_email' => 'mail@example.test',
                'support_email' => '',
                'recruitment_email' => '',
                'reply_to_email' => '',
                'primary_whatsapp' => '',
                'recruitment_whatsapp' => '',
                'website_url' => 'https://hire.example.test',
                'office_address' => '',
                'copyright_text' => '',
                'default_seo_title' => '',
                'default_meta_description' => '',
                'notification_emails' => '',
            ]);

        app(MailConfigurationService::class)->applyRuntimeConfiguration();

        $this->assertSame('https://hire.example.test', app(\App\Support\ApplicationUrlResolver::class)->effectiveRootUrl());
    }

    private function makeRecruiter(): \App\Models\User
    {
        return $this->makeStaff(\App\Enums\StaffRoleSlug::Recruiter->value);
    }
}
