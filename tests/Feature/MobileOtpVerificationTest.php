<?php

namespace Tests\Feature;

use App\Contracts\SmsOtpGateway;
use App\Enums\UserActivityType;
use App\Enums\VerificationChannel;
use App\Models\UserActivity;
use App\Models\VerificationChallenge;
use App\Services\Sms\UnconfiguredSmsOtpGateway;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class MobileOtpVerificationTest extends TestCase
{
    public function test_candidate_can_request_otp_without_exposing_the_code(): void
    {
        $candidate = $this->makeCandidate([
            'username' => 'otp-candidate',
            'mobile' => '9876543210',
        ]);

        $this->assertSame('+919876543210', $candidate->fresh()->mobile);
        $this->assertNull($candidate->fresh()->mobile_verified_at);

        $response = $this->actingAs($candidate)->post(route('account.settings.mobile.send-otp'));

        $response->assertRedirect();
        $response->assertSessionHas('status');
        $this->assertCount(1, $this->smsOtpGateway->messages);
        $this->assertSame('+919876543210', $this->smsOtpGateway->lastDestination());
        $this->assertMatchesRegularExpression('/^\d{6}$/', (string) $this->smsOtpGateway->lastCode());

        $challenge = VerificationChallenge::query()->first();
        $this->assertNotNull($challenge);
        $this->assertSame($candidate->id, $challenge->user_id);
        $this->assertSame(VerificationChannel::Mobile, $challenge->channel);
        $this->assertSame('+919876543210', $challenge->destination);
        $this->assertNotSame($this->smsOtpGateway->lastCode(), $challenge->code_hash);
        $this->assertSame(64, strlen($challenge->code_hash));

        $this->assertResponseDoesNotContainOtp($response, (string) $this->smsOtpGateway->lastCode());
        $this->assertDatabaseHas('user_activities', [
            'user_id' => $candidate->id,
            'type' => UserActivityType::SecurityEvent->value,
            'description' => 'Mobile verification was requested.',
        ]);
    }

    public function test_employer_can_request_otp_for_user_mobile_not_company_phone(): void
    {
        $employer = $this->makeEmployer([
            'username' => 'otp-employer',
            'mobile' => '+918888888888',
        ]);
        $employer->employerProfile?->forceFill(['mobile_number' => '9000000099'])->save();

        $this->actingAs($employer)
            ->post(route('account.settings.mobile.send-otp'))
            ->assertRedirect();

        $this->assertSame('+918888888888', $this->smsOtpGateway->lastDestination());
        $this->assertNotSame('+919000000099', $this->smsOtpGateway->lastDestination());
    }

    public function test_guest_cannot_request_otp(): void
    {
        $this->post(route('account.settings.mobile.send-otp'))->assertRedirect();
        $this->assertSame([], $this->smsOtpGateway->messages);
    }

    public function test_send_rejects_client_supplied_user_id_and_mobile(): void
    {
        $owner = $this->makeCandidate(['username' => 'otp-owner', 'mobile' => '9123456789']);
        $other = $this->makeCandidate(['username' => 'otp-other', 'mobile' => '9234567890']);

        $this->actingAs($owner)
            ->post(route('account.settings.mobile.send-otp'), [
                'user_id' => $other->id,
                'mobile' => $other->mobile,
            ])
            ->assertSessionHasErrors(['user_id', 'mobile']);

        $this->assertSame([], $this->smsOtpGateway->messages);
    }

    public function test_empty_mobile_cannot_request_otp(): void
    {
        $candidate = $this->makeCandidate(['username' => 'otp-empty']);

        $this->actingAs($candidate)
            ->post(route('account.settings.mobile.send-otp'))
            ->assertSessionHasErrors('mobile');
    }

    public function test_invalid_mobile_is_rejected_and_indian_formats_normalize(): void
    {
        $candidate = $this->makeCandidate(['username' => 'otp-format']);

        $this->actingAs($candidate)
            ->put(route('account.settings.account.update'), [
                'username' => 'otp-format',
                'mobile' => '12345',
                'preferred_language' => 'en',
            ])
            ->assertSessionHasErrors('mobile');

        foreach (['9876500001', '919876500001', '+919876500001'] as $input) {
            $this->actingAs($candidate)
                ->put(route('account.settings.account.update'), [
                    'username' => 'otp-format',
                    'mobile' => $input,
                    'preferred_language' => 'en',
                ])
                ->assertSessionHasNoErrors();

            $this->assertSame('+919876500001', $candidate->fresh()->mobile);
        }
    }

    public function test_duplicate_normalized_mobile_is_rejected(): void
    {
        $this->makeCandidate(['username' => 'otp-taken', 'mobile' => '+919811122233']);
        $candidate = $this->makeCandidate(['username' => 'otp-new']);

        $this->actingAs($candidate)
            ->put(route('account.settings.account.update'), [
                'username' => 'otp-new',
                'mobile' => '9811122233',
                'preferred_language' => 'en',
            ])
            ->assertSessionHasErrors('mobile');
    }

    public function test_correct_otp_verifies_and_writes_mobile_verified_at(): void
    {
        $candidate = $this->makeCandidate(['username' => 'otp-ok', 'mobile' => '9810000001']);
        $this->actingAs($candidate)->post(route('account.settings.mobile.send-otp'))->assertRedirect();
        $code = (string) $this->smsOtpGateway->lastCode();

        $this->actingAs($candidate)
            ->post(route('account.settings.mobile.verify'), ['otp' => $code])
            ->assertRedirect()
            ->assertSessionHas('success');

        $candidate->refresh();
        $this->assertNotNull($candidate->mobile_verified_at);
        $this->assertTrue($candidate->mobileVerified());
        $this->assertNotNull(VerificationChallenge::query()->whereNotNull('used_at')->first());
        $this->assertDatabaseHas('user_activities', [
            'user_id' => $candidate->id,
            'type' => UserActivityType::MobileVerified->value,
            'description' => 'Mobile number was verified.',
        ]);

        UserActivity::query()->get()->each(function (UserActivity $activity) use ($code): void {
            $this->assertStringNotContainsString($code, (string) $activity->description);
            $this->assertStringNotContainsString($code, json_encode($activity->metadata) ?: '');
        });
    }

    public function test_incorrect_otp_is_rejected_without_verifying(): void
    {
        $candidate = $this->makeCandidate(['username' => 'otp-bad', 'mobile' => '9810000002']);
        $this->actingAs($candidate)->post(route('account.settings.mobile.send-otp'));

        $this->actingAs($candidate)
            ->post(route('account.settings.mobile.verify'), ['otp' => '000000'])
            ->assertSessionHasErrors('otp');

        $this->assertNull($candidate->fresh()->mobile_verified_at);
    }

    public function test_expired_otp_is_rejected(): void
    {
        $candidate = $this->makeCandidate(['username' => 'otp-exp', 'mobile' => '9810000003']);
        $this->actingAs($candidate)->post(route('account.settings.mobile.send-otp'));
        $code = (string) $this->smsOtpGateway->lastCode();

        Carbon::setTestNow(now()->addMinutes(11));

        $this->actingAs($candidate)
            ->post(route('account.settings.mobile.verify'), ['otp' => $code])
            ->assertSessionHasErrors('otp');

        $this->assertNull($candidate->fresh()->mobile_verified_at);
        Carbon::setTestNow();
    }

    public function test_used_otp_cannot_be_reused(): void
    {
        $candidate = $this->makeCandidate(['username' => 'otp-used', 'mobile' => '9810000004']);
        $this->actingAs($candidate)->post(route('account.settings.mobile.send-otp'));
        $code = (string) $this->smsOtpGateway->lastCode();

        $this->actingAs($candidate)->post(route('account.settings.mobile.verify'), ['otp' => $code])->assertSessionHas('success');
        $verifiedAt = $candidate->fresh()->mobile_verified_at;

        $this->actingAs($candidate)
            ->post(route('account.settings.mobile.verify'), ['otp' => $code])
            ->assertSessionHasErrors('otp');

        $this->assertTrue($candidate->fresh()->mobile_verified_at->equalTo($verifiedAt));
    }

    public function test_maximum_attempts_are_enforced(): void
    {
        $candidate = $this->makeCandidate(['username' => 'otp-max', 'mobile' => '9810000005']);
        $this->actingAs($candidate)->post(route('account.settings.mobile.send-otp'));
        $code = (string) $this->smsOtpGateway->lastCode();

        for ($i = 0; $i < 5; $i++) {
            $this->actingAs($candidate)
                ->post(route('account.settings.mobile.verify'), ['otp' => '111111'])
                ->assertSessionHasErrors('otp');
        }

        $this->actingAs($candidate)
            ->post(route('account.settings.mobile.verify'), ['otp' => $code])
            ->assertSessionHasErrors('otp');

        $this->assertNull($candidate->fresh()->mobile_verified_at);
    }

    public function test_resend_cooldown_and_new_challenge_invalidates_previous(): void
    {
        $candidate = $this->makeCandidate(['username' => 'otp-cd', 'mobile' => '9810000006']);
        $this->actingAs($candidate)->post(route('account.settings.mobile.send-otp'))->assertRedirect();
        $first = (string) $this->smsOtpGateway->lastCode();

        $this->actingAs($candidate)
            ->post(route('account.settings.mobile.send-otp'))
            ->assertSessionHasErrors('mobile');

        Carbon::setTestNow(now()->addSeconds(61));

        $this->actingAs($candidate)->post(route('account.settings.mobile.send-otp'))->assertRedirect();
        $second = (string) $this->smsOtpGateway->lastCode();
        $this->assertNotSame($first, $second);

        $this->actingAs($candidate)
            ->post(route('account.settings.mobile.verify'), ['otp' => $first])
            ->assertSessionHasErrors('otp');

        $this->actingAs($candidate)
            ->post(route('account.settings.mobile.verify'), ['otp' => $second])
            ->assertSessionHas('success');

        Carbon::setTestNow();
    }

    public function test_changing_mobile_clears_verification_but_same_normalized_number_does_not(): void
    {
        $candidate = $this->makeCandidate([
            'username' => 'otp-change',
            'mobile' => '+919700000001',
            'mobile_verified_at' => now(),
        ]);

        $this->actingAs($candidate)
            ->put(route('account.settings.account.update'), [
                'username' => 'otp-change',
                'mobile' => '9700000001',
                'preferred_language' => 'en',
            ])
            ->assertSessionHasNoErrors();

        $this->assertNotNull($candidate->fresh()->mobile_verified_at);
        $this->assertSame('+919700000001', $candidate->fresh()->mobile);

        $this->actingAs($candidate)
            ->put(route('account.settings.account.update'), [
                'username' => 'otp-renamed',
                'mobile' => '+919700000001',
                'preferred_language' => 'en',
            ])
            ->assertSessionHasNoErrors();

        $this->assertNotNull($candidate->fresh()->mobile_verified_at);

        $this->actingAs($candidate)
            ->put(route('account.settings.account.update'), [
                'username' => 'otp-renamed',
                'mobile' => '+918700000001',
                'preferred_language' => 'en',
            ])
            ->assertSessionHasNoErrors();

        $this->assertNull($candidate->fresh()->mobile_verified_at);
        $this->assertSame('+918700000001', $candidate->fresh()->mobile);
    }

    public function test_client_cannot_set_mobile_verified_at(): void
    {
        $candidate = $this->makeCandidate(['username' => 'otp-flag', 'mobile' => '9700000002']);

        $this->actingAs($candidate)
            ->put(route('account.settings.account.update'), [
                'username' => 'otp-flag',
                'mobile' => '9700000002',
                'preferred_language' => 'en',
                'mobile_verified_at' => now()->toDateTimeString(),
            ])
            ->assertSessionHasErrors('mobile_verified_at');

        $this->assertNull($candidate->fresh()->mobile_verified_at);
    }

    public function test_candidate_cannot_verify_another_users_challenge(): void
    {
        $owner = $this->makeCandidate(['username' => 'otp-a', 'mobile' => '9700000003']);
        $other = $this->makeCandidate(['username' => 'otp-b', 'mobile' => '9700000004']);

        $this->actingAs($owner)->post(route('account.settings.mobile.send-otp'));
        $code = (string) $this->smsOtpGateway->lastCode();

        $this->actingAs($other)
            ->post(route('account.settings.mobile.verify'), ['otp' => $code])
            ->assertSessionHasErrors('otp');

        $this->assertNull($owner->fresh()->mobile_verified_at);
        $this->assertNull($other->fresh()->mobile_verified_at);
    }

    public function test_admin_cannot_use_account_otp_endpoints(): void
    {
        $admin = $this->makeAdmin();
        $candidate = $this->makeCandidate(['username' => 'otp-admin-target', 'mobile' => '9700000005']);

        $this->actingAs($admin)
            ->post(route('account.settings.mobile.send-otp'))
            ->assertRedirect();

        $this->assertSame([], $this->smsOtpGateway->messages);
        $this->assertNull($candidate->fresh()->mobile_verified_at);
    }

    public function test_unconfigured_gateway_does_not_fake_delivery(): void
    {
        $this->app->instance(SmsOtpGateway::class, new UnconfiguredSmsOtpGateway());
        $candidate = $this->makeCandidate(['username' => 'otp-sms', 'mobile' => '9700000006']);

        $this->actingAs($candidate)
            ->post(route('account.settings.mobile.send-otp'))
            ->assertSessionHasErrors('mobile');

        $this->assertSame(0, VerificationChallenge::query()->count());
    }

    public function test_rate_limiter_protects_per_user(): void
    {
        $candidate = $this->makeCandidate(['username' => 'otp-rl', 'mobile' => '9700000007']);

        for ($i = 0; $i < 5; $i++) {
            Carbon::setTestNow(now()->addSeconds(61));
            $this->actingAs($candidate)->post(route('account.settings.mobile.send-otp'))->assertRedirect();
        }

        Carbon::setTestNow(now()->addSeconds(61));
        $this->actingAs($candidate)->post(route('account.settings.mobile.send-otp'))->assertStatus(429);

        Carbon::setTestNow();
    }

    public function test_settings_pages_include_mobile_verified_flag_not_otp(): void
    {
        $candidate = $this->makeCandidate(['username' => 'otp-ui', 'mobile' => '9700000008']);

        $this->actingAs($candidate)
            ->get(route('account.settings.account'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Account/Settings/Account')
                ->where('profile.mobile', '+919700000008')
                ->where('profile.mobile_verified', false)
                ->missing('otp')
                ->missing('code'));
    }

    /**
     * @param  \Illuminate\Testing\TestResponse<\Illuminate\Http\RedirectResponse>  $response
     */
    private function assertResponseDoesNotContainOtp($response, string $otp): void
    {
        $this->assertNotSame('', $otp);
        $content = $response->getContent() ?: '';
        $this->assertStringNotContainsString($otp, $content);
        $this->assertStringNotContainsString('"otp"', strtolower($content));
        $session = session()->all();
        $encoded = json_encode($session) ?: '';
        $this->assertStringNotContainsString($otp, $encoded);
    }
}
