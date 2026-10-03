<?php

namespace Tests\Feature;

use App\Enums\StaffRoleSlug;
use App\Mail\ApplicationSubmittedApplicantMail;
use App\Mail\ContactEnquirySubmittedSubmitterMail;
use App\Models\Application;
use App\Models\ContactEnquiry;
use App\Models\EmployerEnquiry;
use App\Models\WebsiteSetting;
use App\Support\WhatsAppNumber;
use Illuminate\Support\Facades\Mail;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CompanyCommunicationSettingsTest extends TestCase
{
    public function test_authorized_admin_can_update_company_email_and_whatsapp_settings(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->put(route('admin.settings.website'), $this->websitePayload([
                'office_email' => ' Primary@Example.TEST ',
                'support_email' => 'help@example.test',
                'recruitment_email' => 'jobs@example.test',
                'reply_to_email' => 'reply@example.test',
                'primary_whatsapp' => '9876543210',
                'recruitment_whatsapp' => '+91 99887 76655',
            ]))
            ->assertRedirect(route('admin.settings.edit', ['tab' => 'website']));

        $row = WebsiteSetting::query()->where('lock_key', 'default')->first();
        $this->assertNotNull($row);
        $this->assertSame('primary@example.test', $row->office_email);
        $this->assertSame('help@example.test', $row->support_email);
        $this->assertSame('jobs@example.test', $row->recruitment_email);
        $this->assertSame('reply@example.test', $row->reply_to_email);
        $this->assertSame('+919876543210', $row->primary_whatsapp);
        $this->assertSame('+919988776655', $row->recruitment_whatsapp);

        $this->actingAs($admin)
            ->get(route('admin.settings.edit'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Settings')
                ->where('websiteSettings.office_email', 'primary@example.test')
                ->where('websiteSettings.primary_whatsapp', '+919876543210')
                ->missing('websiteSettings.smtp_password')
                ->missing('websiteSettings.mail_password')
                ->missing('websiteSettings.meta_access_token'));
    }

    public function test_content_manager_can_update_communication_settings_and_recruiter_cannot(): void
    {
        $content = $this->makeStaff(StaffRoleSlug::ContentManager->value);
        $this->actingAs($content)
            ->put(route('admin.settings.website'), $this->websitePayload([
                'office_email' => 'content@example.test',
            ]))
            ->assertRedirect();

        $this->assertSame('content@example.test', WebsiteSetting::query()->value('office_email'));

        $recruiter = $this->makeStaff(StaffRoleSlug::Recruiter->value);
        $this->actingAs($recruiter)
            ->put(route('admin.settings.website'), $this->websitePayload([
                'office_email' => 'recruiter@example.test',
            ]))
            ->assertForbidden();

        $this->assertSame('content@example.test', WebsiteSetting::query()->value('office_email'));
    }

    public function test_candidates_and_employers_cannot_update_company_communication_settings(): void
    {
        $payload = $this->websitePayload(['office_email' => 'stolen@example.test']);

        $this->actingAs($this->makeCandidate())
            ->put(route('admin.settings.website'), $payload)
            ->assertForbidden();

        $this->actingAs($this->makeEmployer())
            ->put(route('admin.settings.website'), $payload)
            ->assertForbidden();

        $this->actingAs($this->makeCandidate())
            ->get(route('admin.settings.edit'))
            ->assertForbidden();
    }

    public function test_invalid_email_and_whatsapp_values_are_rejected(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->put(route('admin.settings.website'), $this->websitePayload([
                'office_email' => 'not-an-email',
            ]))
            ->assertSessionHasErrors('office_email');

        $this->actingAs($admin)
            ->put(route('admin.settings.website'), $this->websitePayload([
                'primary_whatsapp' => '12345',
            ]))
            ->assertSessionHasErrors('primary_whatsapp');

        $this->actingAs($admin)
            ->put(route('admin.settings.website'), $this->websitePayload([
                'smtp_password' => 'should-not-save',
            ]))
            ->assertSessionHasErrors('smtp_password');
    }

    public function test_application_mail_uses_admin_configured_from_and_reply_to(): void
    {
        $this->actingAs($this->makeAdmin())
            ->put(route('admin.settings.website'), $this->websitePayload([
                'company_name' => 'OurNear',
                'office_email' => 'primary@example.test',
                'recruitment_email' => 'jobs@example.test',
                'reply_to_email' => 'reply@example.test',
            ]))
            ->assertRedirect();

        $this->assertSame('primary@example.test', config('mail.from.address'));

        Mail::fake();
        $application = Application::factory()->create();
        Mail::to('candidate@example.com')->send(new ApplicationSubmittedApplicantMail($application));

        Mail::assertQueued(ApplicationSubmittedApplicantMail::class, function (ApplicationSubmittedApplicantMail $mail) {
            return $mail->hasFrom('primary@example.test', 'OurNear')
                && $mail->hasReplyTo('jobs@example.test', 'OurNear');
        });

        $this->actingAs($this->makeAdmin())
            ->put(route('admin.settings.website'), $this->websitePayload([
                'office_email' => 'changed@example.test',
                'recruitment_email' => 'new-jobs@example.test',
            ]))
            ->assertRedirect();

        $this->assertSame('changed@example.test', config('mail.from.address'));

        Mail::to('candidate@example.com')->send(new ApplicationSubmittedApplicantMail($application));
        Mail::assertQueued(ApplicationSubmittedApplicantMail::class, function (ApplicationSubmittedApplicantMail $mail) {
            return $mail->hasFrom('changed@example.test')
                && $mail->hasReplyTo('new-jobs@example.test');
        });
    }

    public function test_contact_enquiry_mail_uses_support_reply_to(): void
    {
        $this->actingAs($this->makeAdmin())
            ->put(route('admin.settings.website'), $this->websitePayload([
                'office_email' => 'primary@example.test',
                'support_email' => 'help@example.test',
                'reply_to_email' => 'reply@example.test',
            ]))
            ->assertRedirect();

        Mail::fake();
        $enquiry = ContactEnquiry::factory()->create();
        Mail::to('visitor@example.com')->send(new ContactEnquirySubmittedSubmitterMail($enquiry));

        Mail::assertQueued(ContactEnquirySubmittedSubmitterMail::class, function (ContactEnquirySubmittedSubmitterMail $mail) {
            return $mail->hasFrom('primary@example.test')
                && $mail->hasReplyTo('help@example.test');
        });
    }

    public function test_company_whatsapp_links_use_configured_number_and_personal_numbers_stay_separate(): void
    {
        $this->actingAs($this->makeAdmin())
            ->put(route('admin.settings.website'), $this->websitePayload([
                'primary_whatsapp' => '+91 98765 43210',
                'recruitment_whatsapp' => '9988776655',
            ]))
            ->assertRedirect();

        $this->get(route('contact'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Contact')
                ->where('content.contact.whatsapp_url', 'https://wa.me/919876543210')
                ->where('content.contact.whatsapp', '+91 98765 43210')
                ->where('publicContent.contact.whatsapp_url', 'https://wa.me/919876543210')
                ->where('publicContent.contact.recruitment_whatsapp_url', 'https://wa.me/919988776655'));

        $this->get(route('for-employers'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('ForEmployers')
                ->where('content.contact.recruitment_email', null)
                ->where('content.contact.recruitment_whatsapp_url', 'https://wa.me/919988776655'));

        $enquiry = EmployerEnquiry::factory()->create(['mobile_number' => '07000001111']);
        $payload = $enquiry->toAdminArray();
        $this->assertSame('https://wa.me/07000001111', $payload['whatsapp_url']);
        $this->assertNotSame('https://wa.me/919876543210', $payload['whatsapp_url']);

        $candidate = $this->makeCandidate(['mobile' => '9000000001']);
        $this->assertSame('+919000000001', $candidate->fresh()->mobile);
        $this->assertNotSame('+919876543210', $candidate->fresh()->mobile);

        $this->actingAs($candidate)
            ->get(route('account.home'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->missing('websiteSettings')
                ->missing('smtp_password'));
    }

    public function test_application_paths_do_not_hard_code_company_mailboxes(): void
    {
        $roots = [app_path(), resource_path('js')];
        foreach ($roots as $root) {
            $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root));
            foreach ($iterator as $file) {
                if (! $file->isFile()) {
                    continue;
                }
                $extension = strtolower($file->getExtension());
                if (! in_array($extension, ['php', 'tsx', 'ts', 'js'], true)) {
                    continue;
                }

                $contents = file_get_contents($file->getPathname());
                $this->assertIsString($contents);
                $this->assertStringNotContainsString(
                    '@ournear.com',
                    $contents,
                    $file->getPathname().' must not hard-code an OurNear mailbox.',
                );
            }
        }
    }

    public function test_whatsapp_normalizer_accepts_india_formats_and_rejects_junk(): void
    {
        $this->assertSame('+919876543210', WhatsAppNumber::normalize('9876543210'));
        $this->assertSame('+919876543210', WhatsAppNumber::normalize('09876543210'));
        $this->assertSame('+919876543210', WhatsAppNumber::normalize('919876543210'));
        $this->assertSame('+919876543210', WhatsAppNumber::normalize('+91 98765 43210'));
        $this->assertNull(WhatsAppNumber::normalize(''));
        $this->assertFalse(WhatsAppNumber::normalize('12345'));
        $this->assertSame('https://wa.me/919876543210', WhatsAppNumber::waMeUrl('+919876543210'));
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function websitePayload(array $overrides = []): array
    {
        return array_merge([
            'company_name' => 'OurNear',
            'office_phone' => '',
            'office_email' => '',
            'support_email' => '',
            'recruitment_email' => '',
            'reply_to_email' => '',
            'primary_whatsapp' => '',
            'recruitment_whatsapp' => '',
            'website_url' => '',
            'office_address' => '',
            'copyright_text' => '',
            'default_seo_title' => '',
            'default_meta_description' => '',
            'notification_emails' => '',
        ], $overrides);
    }
}
