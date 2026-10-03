<?php

namespace Tests\Feature;

use App\Contracts\WhatsAppGateway;
use App\Enums\WhatsAppBusinessIdentity;
use App\Enums\WhatsAppDeliveryStatus;
use App\Enums\WhatsAppMessagePurpose;
use App\Enums\WhatsAppProviderError;
use App\Exceptions\WhatsAppProviderException;
use App\Models\WhatsAppOutboundMessage;
use App\Services\WebsiteSettingService;
use App\Services\WhatsApp\MetaWhatsAppCloudGateway;
use App\Services\WhatsApp\UnconfiguredWhatsAppGateway;
use App\Services\WhatsAppMessagingService;
use App\Support\WhatsAppCloudConfig;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class WhatsAppCloudFoundationTest extends TestCase
{
    public function test_unconfigured_provider_fails_honestly(): void
    {
        $this->app->instance(WhatsAppGateway::class, new UnconfiguredWhatsAppGateway());
        $this->seedCompanyWhatsapp();

        $this->expectException(WhatsAppProviderException::class);
        $this->expectExceptionMessage('WhatsApp Cloud API is not configured yet.');

        app(WhatsAppMessagingService::class)->sendTemplate(
            '+919876543210',
            'operational_update',
            'en',
            ['Interview reminder'],
        );
    }

    public function test_recording_gateway_sends_normalized_template_without_storing_body_copy(): void
    {
        $this->seedCompanyWhatsapp();

        $row = app(WhatsAppMessagingService::class)->sendTemplate(
            '9876543210',
            'operational_update',
            'en_US',
            ['Candidate shortlisted'],
            WhatsAppMessagePurpose::Transactional,
            WhatsAppBusinessIdentity::Primary,
        );

        $last = $this->whatsAppGateway->last();
        $this->assertNotNull($last);
        $this->assertSame('+919876543210', $last->recipientE164);
        $this->assertSame('operational_update', $last->templateName);
        $this->assertSame('en_US', $last->language);
        $this->assertSame(['Candidate shortlisted'], $last->bodyParameters);
        $this->assertSame('primary', $last->identity);
        $this->assertSame('+919811122233', $last->companySenderE164);

        $this->assertSame('wamid.test.1', $row->provider_message_id);
        $this->assertSame('+919876543210', $row->recipient);
        $this->assertSame(WhatsAppDeliveryStatus::Accepted, $row->status);
        $this->assertDatabaseMissing('whatsapp_outbound_messages', [
            'provider_message_id' => 'wamid.test.1',
            'template_name' => 'Candidate shortlisted',
        ]);
    }

    public function test_missing_company_whatsapp_identity_blocks_send(): void
    {
        $this->expectException(\Illuminate\Validation\ValidationException::class);

        app(WhatsAppMessagingService::class)->sendTemplate(
            '+919876543210',
            'operational_update',
        );
    }

    public function test_provider_failure_does_not_create_an_outbound_row(): void
    {
        $this->seedCompanyWhatsapp();
        $this->whatsAppGateway->failure = new WhatsAppProviderException(
            'WhatsApp Cloud API authentication failed.',
            WhatsAppProviderError::Authentication,
        );

        try {
            app(WhatsAppMessagingService::class)->sendTemplate('+919876543210', 'operational_update');
            $this->fail('Expected provider exception');
        } catch (WhatsAppProviderException $exception) {
            $this->assertSame(WhatsAppProviderError::Authentication, $exception->error);
        }

        $this->assertSame(0, WhatsAppOutboundMessage::query()->count());
    }

    public function test_meta_gateway_posts_template_payload_and_maps_auth_errors(): void
    {
        config([
            'whatsapp.provider' => 'cloud',
            'whatsapp.graph.version' => 'v21.0',
            'whatsapp.graph.base_url' => 'https://graph.facebook.com',
            'whatsapp.access_token' => 'unit-test-access-token',
            'whatsapp.phone_number_id' => '555000',
        ]);

        $this->assertTrue(WhatsAppCloudConfig::isReady());

        Http::fake([
            'https://graph.facebook.com/v21.0/555000/messages' => Http::response([
                'messages' => [['id' => 'wamid.graph.1']],
            ], 200),
        ]);

        $this->app->forgetInstance(WhatsAppGateway::class);
        $this->app->instance(WhatsAppGateway::class, $this->app->make(MetaWhatsAppCloudGateway::class));
        $this->seedCompanyWhatsapp();

        $row = app(WhatsAppMessagingService::class)->sendTemplate(
            '+919700000001',
            'operational_update',
            'en',
            ['Hello'],
        );

        $this->assertSame('wamid.graph.1', $row->provider_message_id);

        Http::assertSent(function ($request) {
            $payload = $request->data();

            return $request->url() === 'https://graph.facebook.com/v21.0/555000/messages'
                && ($payload['to'] ?? null) === '919700000001'
                && ($payload['type'] ?? null) === 'template'
                && ($payload['template']['name'] ?? null) === 'operational_update'
                && ($payload['template']['language']['code'] ?? null) === 'en'
                && ($payload['template']['components'][0]['parameters'][0]['text'] ?? null) === 'Hello'
                && $request->hasHeader('Authorization');
        });
    }

    public function test_meta_authentication_failure_is_safe(): void
    {
        config([
            'whatsapp.provider' => 'cloud',
            'whatsapp.graph.version' => 'v21.0',
            'whatsapp.access_token' => 'unit-test-access-token',
            'whatsapp.phone_number_id' => '555000',
        ]);

        Http::fake([
            'https://graph.facebook.com/*' => Http::response([
                'error' => ['code' => 190, 'message' => 'Invalid OAuth access token.'],
            ], 401),
        ]);

        $gateway = $this->app->make(MetaWhatsAppCloudGateway::class);
        $this->seedCompanyWhatsapp();
        $this->app->instance(WhatsAppGateway::class, $gateway);

        try {
            app(WhatsAppMessagingService::class)->sendTemplate('+919700000002', 'operational_update');
            $this->fail('Expected authentication failure');
        } catch (WhatsAppProviderException $exception) {
            $this->assertSame(WhatsAppProviderError::Authentication, $exception->error);
            $this->assertStringNotContainsString('unit-test-access-token', $exception->getMessage());
            $this->assertStringNotContainsString('Invalid OAuth', $exception->getMessage());
        }
    }

    public function test_admin_settings_do_not_expose_cloud_secrets(): void
    {
        config(['whatsapp.access_token' => 'secret-should-not-appear']);
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->get(route('admin.settings.edit'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Settings')
                ->missing('websiteSettings.access_token')
                ->missing('websiteSettings.whatsapp_access_token')
                ->missing('websiteSettings.verify_token')
                ->has('websiteSettings.primary_whatsapp'));

        $this->actingAs($admin)
            ->get(route('admin.settings.edit'))
            ->assertDontSee('secret-should-not-appear', false);
    }

    public function test_candidate_and_employer_cannot_change_api_configuration_via_settings(): void
    {
        $this->actingAs($this->makeCandidate())
            ->put(route('admin.settings.website'), [
                'company_name' => 'Stolen',
                'primary_whatsapp' => '9876500000',
            ])
            ->assertForbidden();

        $this->actingAs($this->makeEmployer())
            ->put(route('admin.settings.website'), [
                'company_name' => 'Stolen',
            ])
            ->assertForbidden();
    }

    public function test_webhook_verification_accepts_matching_token_only(): void
    {
        config(['whatsapp.verify_token' => 'verify-test-token']);

        $this->get(route('webhooks.whatsapp.verify', [
            'hub.mode' => 'subscribe',
            'hub.verify_token' => 'verify-test-token',
            'hub.challenge' => 'challenge-123',
        ]))->assertOk()->assertSee('challenge-123', false);

        $this->get(route('webhooks.whatsapp.verify', [
            'hub.mode' => 'subscribe',
            'hub.verify_token' => 'wrong',
            'hub.challenge' => 'challenge-123',
        ]))->assertForbidden();
    }

    public function test_unsigned_webhook_cannot_mutate_status(): void
    {
        $this->seedCompanyWhatsapp();
        $row = app(WhatsAppMessagingService::class)->sendTemplate('+919876543210', 'operational_update');

        config(['whatsapp.app_secret' => 'webhook-secret']);

        $this->postJson(route('webhooks.whatsapp.callback'), $this->statusPayload($row->provider_message_id, 'delivered'))
            ->assertForbidden();

        $this->assertSame(WhatsAppDeliveryStatus::Accepted, $row->fresh()->status);
    }

    public function test_signed_webhook_updates_status_and_ignores_duplicates(): void
    {
        $this->seedCompanyWhatsapp();
        $row = app(WhatsAppMessagingService::class)->sendTemplate('+919876543210', 'operational_update');

        config(['whatsapp.app_secret' => 'webhook-secret']);
        $payload = $this->statusPayload($row->provider_message_id, 'delivered');
        $raw = json_encode($payload, JSON_THROW_ON_ERROR);
        $signature = 'sha256='.hash_hmac('sha256', $raw, 'webhook-secret');

        $this->call('POST', route('webhooks.whatsapp.callback'), [], [], [], [
            'HTTP_X_HUB_SIGNATURE_256' => $signature,
            'CONTENT_TYPE' => 'application/json',
        ], $raw)->assertNoContent();

        $this->assertSame(WhatsAppDeliveryStatus::Delivered, $row->fresh()->status);

        $this->call('POST', route('webhooks.whatsapp.callback'), [], [], [], [
            'HTTP_X_HUB_SIGNATURE_256' => $signature,
            'CONTENT_TYPE' => 'application/json',
        ], $raw)->assertNoContent();

        $this->assertSame(WhatsAppDeliveryStatus::Delivered, $row->fresh()->status);
        $this->assertSame(1, WhatsAppOutboundMessage::query()->count());
    }

    public function test_failed_status_does_not_regress_to_sent(): void
    {
        $this->seedCompanyWhatsapp();
        $row = app(WhatsAppMessagingService::class)->sendTemplate('+919876543210', 'operational_update');
        app(WhatsAppMessagingService::class)->applyStatus($row->provider_message_id, WhatsAppDeliveryStatus::Failed, 131026);
        app(WhatsAppMessagingService::class)->applyStatus($row->provider_message_id, WhatsAppDeliveryStatus::Sent);

        $this->assertSame(WhatsAppDeliveryStatus::Failed, $row->fresh()->status);
    }

    private function seedCompanyWhatsapp(): void
    {
        $row = app(WebsiteSettingService::class)->current();
        $row->forceFill([
            'primary_whatsapp' => '+919811122233',
            'recruitment_whatsapp' => '+919811122244',
        ])->save();
    }

    /**
     * @return array<string, mixed>
     */
    private function statusPayload(string $id, string $status): array
    {
        return [
            'object' => 'whatsapp_business_account',
            'entry' => [[
                'id' => 'waba-test',
                'changes' => [[
                    'field' => 'messages',
                    'value' => [
                        'statuses' => [[
                            'id' => $id,
                            'status' => $status,
                            'timestamp' => (string) time(),
                        ]],
                    ],
                ]],
            ]],
        ];
    }
}
