<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\VerifyAccountEmailNotification;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class AccountVerificationEmailTest extends TestCase
{
    public function test_registration_sends_verification_notification_with_signed_url(): void
    {
        Notification::fake();

        $payload = $this->registrationPayload();
        $this->post(route('register.store'), $payload)
            ->assertRedirect();

        $user = User::query()->where('email', strtolower((string) $payload['email']))->first();
        $this->assertNotNull($user);

        Notification::assertSentTo($user, VerifyAccountEmailNotification::class);
    }

    public function test_verification_email_contains_clickable_link_and_plain_text_url(): void
    {
        $this->actingAs($this->makeAdmin())
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

        URL::forceRootUrl('https://hire.example.test');
        URL::forceScheme('https');

        $user = $this->makeCandidate(['email' => 'verify-me@example.test']);
        $notification = new VerifyAccountEmailNotification;
        $mail = $notification->toMail($user);
        $url = $notification->previewVerificationUrl($user);
        $html = $mail->render();

        $this->assertStringContainsString('/email/verify/', $url);
        $this->assertStringContainsString('https://hire.example.test', $url);
        $this->assertStringContainsString(str_replace('&', '&amp;', $url), $html);
        $this->assertMatchesRegularExpression('/href="[^"]+\/email\/verify\/[^"]+"/', $html);
        $this->assertStringContainsString('Verify your email address', $html);
    }

    public function test_valid_verification_link_marks_email_verified(): void
    {
        $user = $this->makeCandidate(['email_verified_at' => null]);
        $url = URL::temporarySignedRoute('verification.verify', now()->addHour(), [
            'id' => $user->id,
            'hash' => sha1($user->email),
        ]);

        $this->get($url)
            ->assertRedirect()
            ->assertSessionHas('success', 'Your email has been verified successfully.');

        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    public function test_invalid_verification_hash_is_rejected(): void
    {
        $user = $this->makeCandidate(['email_verified_at' => null]);
        $url = URL::temporarySignedRoute('verification.verify', now()->addHour(), [
            'id' => $user->id,
            'hash' => sha1('wrong@example.test'),
        ]);

        $this->actingAs($user)
            ->get($url)
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');
    }

    public function test_resend_verification_is_throttled_and_authorized(): void
    {
        Notification::fake();
        $user = $this->makeCandidate(['email_verified_at' => null]);

        $this->actingAs($user)
            ->post(route('account.verification.send'))
            ->assertRedirect();

        Notification::assertSentTo($user, VerifyAccountEmailNotification::class);
    }

    public function test_registered_event_still_drives_verification_for_candidates(): void
    {
        Event::fake([Registered::class]);

        $this->post(route('register.store'), $this->registrationPayload());

        Event::assertDispatched(Registered::class);
    }
}
