<?php

namespace Tests\Feature;

use App\Contracts\WhatsAppGateway;
use App\Enums\UserActivityType;
use App\Enums\VerificationChannel;
use App\Enums\WhatsAppMessagePurpose;
use App\Models\VerificationChallenge;
use App\Models\WhatsAppOutboundMessage;
use App\Services\WebsiteSettingService;
use App\Services\WhatsApp\UnconfiguredWhatsAppGateway;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class WhatsAppOtpVerificationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seedCompanyWhatsapp();
        config([
            'whatsapp.otp.template_name' => 'authentication_code',
            'whatsapp.otp.template_language' => 'en',
            'whatsapp.otp.include_copy_button' => true,
        ]);
    }

    public function test_indian_whatsapp_numbers_normalize_and_invalid_are_rejected(): void
    {
        $candidate = $this->makeCandidate(['username' => 'wa-format']);

        $this->actingAs($candidate)
            ->put(route('account.settings.account.update'), $this->settingsPayload($candidate, [
                'whatsapp_number' => '12345',
            ]))
            ->assertSessionHasErrors('whatsapp_number');

        foreach (['9876500100', '919876500100', '+919876500100'] as $input) {
            $this->actingAs($candidate)
                ->put(route('account.settings.account.update'), $this->settingsPayload($candidate, [
                    'whatsapp_number' => $input,
                ]))
                ->assertSessionHasNoErrors();

            $this->assertSame('+919876500100', $candidate->fresh()->whatsapp_number);
            $this->assertNull($candidate->fresh()->whatsapp_verified_at);
        }
    }

    public function test_same_canonical_number_does_not_clear_verification(): void
    {
        $candidate = $this->makeCandidate([
            'username' => 'wa-same',
            'whatsapp_number' => '+919876500101',
            'whatsapp_verified_at' => now(),
        ]);

        $this->actingAs($candidate)
            ->put(route('account.settings.account.update'), $this->settingsPayload($candidate, [
                'whatsapp_number' => '9876500101',
            ]))
            ->assertSessionHasNoErrors();

        $this->assertNotNull($candidate->fresh()->whatsapp_verified_at);
        $this->assertSame('+919876500101', $candidate->fresh()->whatsapp_number);

        $this->actingAs($candidate)
            ->put(route('account.settings.account.update'), $this->settingsPayload($candidate, [
                'whatsapp_number' => '+919876500199',
            ]))
            ->assertSessionHasNoErrors();

        $this->assertNull($candidate->fresh()->whatsapp_verified_at);
    }

    public function test_company_whatsapp_cannot_be_used_as_personal_identity(): void
    {
        $candidate = $this->makeCandidate(['username' => 'wa-company']);

        $this->actingAs($candidate)
            ->put(route('account.settings.account.update'), $this->settingsPayload($candidate, [
                'whatsapp_number' => '+919811122233',
            ]))
            ->assertSessionHasErrors('whatsapp_number');
    }

    public function test_candidate_can_request_whatsapp_otp_without_exposing_the_code(): void
    {
        $candidate = $this->makeCandidate([
            'username' => 'wa-send',
            'whatsapp_number' => '9876500102',
            'mobile' => '9876500103',
            'mobile_verified_at' => now(),
        ]);

        $response = $this->actingAs($candidate)->post(route('account.settings.whatsapp.send-otp'));
        $response->assertRedirect();
        $response->assertSessionHas('status');

        $last = $this->whatsAppGateway->last();
        $this->assertNotNull($last);
        $this->assertSame('+919876500102', $last->recipientE164);
        $this->assertSame('authentication_code', $last->templateName);
        $this->assertSame('en', $last->language);
        $this->assertSame(WhatsAppMessagePurpose::WhatsappVerification->value, $last->purpose);
        $this->assertMatchesRegularExpression('/^\d{6}$/', $last->bodyParameters[0] ?? '');
        $this->assertSame($last->bodyParameters[0], $last->urlButtonText);

        $challenge = VerificationChallenge::query()->first();
        $this->assertNotNull($challenge);
        $this->assertSame(VerificationChannel::Whatsapp, $challenge->channel);
        $this->assertNotSame($last->bodyParameters[0], $challenge->code_hash);

        $this->assertSame(0, WhatsAppOutboundMessage::query()->where('template_name', $last->bodyParameters[0])->count());
        $this->assertSame(
            WhatsAppMessagePurpose::WhatsappVerification,
            WhatsAppOutboundMessage::query()->first()?->purpose,
        );

        $content = $response->getContent() ?: '';
        $this->assertStringNotContainsString($last->bodyParameters[0], $content);
        $this->assertStringNotContainsString($last->bodyParameters[0], json_encode(session()->all()) ?: '');
        $this->assertNotNull($candidate->fresh()->mobile_verified_at);
        $this->assertNull($candidate->fresh()->whatsapp_verified_at);
    }

    public function test_correct_otp_verifies_whatsapp_not_mobile(): void
    {
        $candidate = $this->makeCandidate([
            'username' => 'wa-ok',
            'whatsapp_number' => '+919876500104',
            'mobile' => '+919876500105',
        ]);

        $this->actingAs($candidate)->post(route('account.settings.whatsapp.send-otp'));
        $code = $this->whatsAppGateway->last()?->bodyParameters[0] ?? '';

        $this->actingAs($candidate)
            ->post(route('account.settings.whatsapp.verify'), ['otp' => $code])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->actingAs($candidate)
            ->post(route('account.settings.whatsapp.verify'), ['otp' => $code])
            ->assertSessionHasErrors('otp');

        $candidate->refresh();
        $this->assertNotNull($candidate->whatsapp_verified_at);
        $this->assertNull($candidate->mobile_verified_at);
        $this->assertDatabaseHas('user_activities', [
            'user_id' => $candidate->id,
            'type' => UserActivityType::WhatsAppVerified->value,
        ]);
    }

    public function test_wrong_expired_used_and_max_attempts_are_enforced(): void
    {
        $candidate = $this->makeCandidate(['username' => 'wa-limits', 'whatsapp_number' => '+919876500106']);
        $this->actingAs($candidate)->post(route('account.settings.whatsapp.send-otp'));
        $code = $this->whatsAppGateway->last()?->bodyParameters[0] ?? '';

        $this->actingAs($candidate)
            ->post(route('account.settings.whatsapp.verify'), ['otp' => '000000'])
            ->assertSessionHasErrors('otp');
        $this->assertNull($candidate->fresh()->whatsapp_verified_at);

        Carbon::setTestNow(now()->addMinutes(11));
        $this->actingAs($candidate)
            ->post(route('account.settings.whatsapp.verify'), ['otp' => $code])
            ->assertSessionHasErrors('otp');
        Carbon::setTestNow();

        $other = $this->makeCandidate(['username' => 'wa-max', 'whatsapp_number' => '+919876500107']);
        $this->actingAs($other)->post(route('account.settings.whatsapp.send-otp'));
        $maxCode = $this->whatsAppGateway->last()?->bodyParameters[0] ?? '';
        for ($i = 0; $i < 5; $i++) {
            $this->actingAs($other)->post(route('account.settings.whatsapp.verify'), ['otp' => '111111'])->assertSessionHasErrors('otp');
        }
        $this->actingAs($other)->post(route('account.settings.whatsapp.verify'), ['otp' => $maxCode])->assertSessionHasErrors('otp');
        $this->assertNull($other->fresh()->whatsapp_verified_at);
    }

    public function test_resend_cooldown_and_previous_challenge_invalidation(): void
    {
        $candidate = $this->makeCandidate(['username' => 'wa-cd', 'whatsapp_number' => '+919876500108']);
        $this->actingAs($candidate)->post(route('account.settings.whatsapp.send-otp'))->assertRedirect();
        $first = $this->whatsAppGateway->last()?->bodyParameters[0] ?? '';

        $this->actingAs($candidate)->post(route('account.settings.whatsapp.send-otp'))->assertSessionHasErrors('whatsapp_number');

        Carbon::setTestNow(now()->addSeconds(61));
        $this->actingAs($candidate)->post(route('account.settings.whatsapp.send-otp'))->assertRedirect();
        $second = $this->whatsAppGateway->last()?->bodyParameters[0] ?? '';
        $this->assertNotSame($first, $second);

        $this->actingAs($candidate)->post(route('account.settings.whatsapp.verify'), ['otp' => $first])->assertSessionHasErrors('otp');
        $this->actingAs($candidate)->post(route('account.settings.whatsapp.verify'), ['otp' => $second])->assertSessionHas('success');
        Carbon::setTestNow();
    }

    public function test_guest_and_cross_user_cannot_verify(): void
    {
        $this->post(route('account.settings.whatsapp.send-otp'))->assertRedirect();

        $owner = $this->makeCandidate(['username' => 'wa-a', 'whatsapp_number' => '+919876500109']);
        $other = $this->makeCandidate(['username' => 'wa-b', 'whatsapp_number' => '+919876500110']);
        $this->actingAs($owner)->post(route('account.settings.whatsapp.send-otp'));
        $code = $this->whatsAppGateway->last()?->bodyParameters[0] ?? '';

        $this->actingAs($other)
            ->post(route('account.settings.whatsapp.verify'), ['otp' => $code])
            ->assertSessionHasErrors('otp');

        $this->assertNull($owner->fresh()->whatsapp_verified_at);
        $this->assertNull($other->fresh()->whatsapp_verified_at);

        $this->actingAs($owner)
            ->post(route('account.settings.whatsapp.send-otp'), ['user_id' => $other->id, 'whatsapp_number' => $other->whatsapp_number])
            ->assertSessionHasErrors(['user_id', 'whatsapp_number']);
    }

    public function test_unconfigured_provider_does_not_verify_or_leave_a_challenge(): void
    {
        $this->app->instance(WhatsAppGateway::class, new UnconfiguredWhatsAppGateway());
        $candidate = $this->makeCandidate(['username' => 'wa-unconf', 'whatsapp_number' => '+919876500111']);

        $this->actingAs($candidate)
            ->post(route('account.settings.whatsapp.send-otp'))
            ->assertSessionHasErrors('whatsapp_number');

        $this->assertSame(0, VerificationChallenge::query()->count());
        $this->assertNull($candidate->fresh()->whatsapp_verified_at);
    }

    public function test_empty_template_name_fails_honestly(): void
    {
        config(['whatsapp.otp.template_name' => '']);
        $candidate = $this->makeCandidate(['username' => 'wa-tpl', 'whatsapp_number' => '+919876500112']);

        $this->actingAs($candidate)
            ->post(route('account.settings.whatsapp.send-otp'))
            ->assertSessionHasErrors('whatsapp_number');

        $this->assertSame([], $this->whatsAppGateway->messages);
        $this->assertSame(0, VerificationChallenge::query()->count());
    }

    public function test_admin_and_client_cannot_force_verified_flag(): void
    {
        $candidate = $this->makeCandidate(['username' => 'wa-flag', 'whatsapp_number' => '+919876500113']);

        $this->actingAs($candidate)
            ->put(route('account.settings.account.update'), $this->settingsPayload($candidate, [
                'whatsapp_verified_at' => now()->toDateTimeString(),
            ]))
            ->assertSessionHasErrors('whatsapp_verified_at');

        $this->actingAs($this->makeAdmin())
            ->post(route('account.settings.whatsapp.send-otp'))
            ->assertRedirect();

        $this->assertNull($candidate->fresh()->whatsapp_verified_at);
    }

    public function test_settings_inertia_does_not_include_otp_or_secrets(): void
    {
        $candidate = $this->makeCandidate(['username' => 'wa-ui', 'whatsapp_number' => '+919876500114']);

        $this->actingAs($candidate)
            ->get(route('account.settings.security'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Account/Settings/Security')
                ->where('profile.whatsapp_number', '+919876500114')
                ->where('profile.whatsapp_verified', false)
                ->missing('otp')
                ->missing('code'));
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function settingsPayload($user, array $overrides = []): array
    {
        return array_merge([
            'username' => $user->username,
            'mobile' => $user->mobile,
            'whatsapp_number' => $user->whatsapp_number,
            'preferred_language' => 'en',
        ], $overrides);
    }

    private function seedCompanyWhatsapp(): void
    {
        $row = app(WebsiteSettingService::class)->current();
        $row->forceFill([
            'primary_whatsapp' => '+919811122233',
            'recruitment_whatsapp' => '+919811122244',
        ])->save();
    }
}
