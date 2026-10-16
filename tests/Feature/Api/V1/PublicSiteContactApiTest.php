<?php

namespace Tests\Feature\Api\V1;

use Tests\TestCase;

class PublicSiteContactApiTest extends TestCase
{
    public function test_public_contact_endpoint_returns_saved_settings_without_authentication(): void
    {
        $this->actingAs($this->makeAdmin())
            ->put(route('admin.settings.website'), $this->websitePayload([
                'office_address' => "Line one\nLine two",
                'support_email' => 'enquiries@example.test',
                'office_phone' => '+91 80 1234 5678',
                'primary_whatsapp' => '9876543210',
            ]))
            ->assertRedirect();

        $this->getJson(route('api.v1.site.contact'))
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'office_address',
                    'enquiry_email',
                    'contact_phone',
                    'whatsapp_number',
                ],
            ])
            ->assertJsonPath('data.office_address', "Line one\nLine two")
            ->assertJsonPath('data.enquiry_email', 'enquiries@example.test')
            ->assertJsonPath('data.contact_phone', '+91 80 1234 5678')
            ->assertJsonPath('data.whatsapp_number', '+91 98765 43210')
            ->assertJsonMissingPath('data.smtp_password')
            ->assertJsonMissingPath('data.office_email')
            ->assertJsonMissingPath('data.notification_emails');
    }

    public function test_enquiry_email_falls_back_to_primary_company_email(): void
    {
        $this->actingAs($this->makeAdmin())
            ->put(route('admin.settings.website'), $this->websitePayload([
                'support_email' => '',
                'office_email' => 'primary@example.test',
            ]))
            ->assertRedirect();

        $this->getJson(route('api.v1.site.contact'))
            ->assertOk()
            ->assertJsonPath('data.enquiry_email', 'primary@example.test');
    }

    public function test_optional_contact_fields_return_null_when_unset(): void
    {
        $this->actingAs($this->makeAdmin())
            ->put(route('admin.settings.website'), $this->websitePayload([
                'office_address' => '',
                'support_email' => '',
                'office_email' => '',
                'office_phone' => '',
                'primary_whatsapp' => '',
            ]))
            ->assertRedirect();

        $response = $this->getJson(route('api.v1.site.contact'))->assertOk();
        $data = $response->json('data');
        $this->assertIsArray($data);
        $this->assertNull($data['office_address']);
        $this->assertNull($data['enquiry_email']);
        $this->assertNull($data['contact_phone']);
        $this->assertNull($data['whatsapp_number']);
    }

    public function test_contact_pages_and_api_share_the_same_source(): void
    {
        $this->actingAs($this->makeAdmin())
            ->put(route('admin.settings.website'), $this->websitePayload([
                'office_address' => '99 Test Street',
                'support_email' => 'hello@example.test',
                'office_phone' => '0800000000',
                'primary_whatsapp' => '+919988776655',
            ]))
            ->assertRedirect();

        $api = $this->getJson(route('api.v1.site.contact'))->json('data');

        $this->get(route('contact'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('content.contact.address', $api['office_address'])
                ->where('content.contact.support_email', $api['enquiry_email'])
                ->where('content.contact.phone', $api['contact_phone'])
                ->where('content.contact.whatsapp', $api['whatsapp_number']));

        $candidate = $this->makeCandidate(['email_verified_at' => now()]);
        $this->actingAs($candidate)
            ->get(route('account.contact'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('content.contact.address', $api['office_address'])
                ->where('content.contact.support_email', $api['enquiry_email']));
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
