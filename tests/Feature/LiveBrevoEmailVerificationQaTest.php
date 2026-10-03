<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Tests\Support\RecordingSmsOtpGateway;
use Tests\Support\RecordingWhatsAppGateway;
use App\Contracts\SmsOtpGateway;
use App\Contracts\WhatsAppGateway;

/**
 * Live Brevo SMTP QA — does not refresh the database.
 */
class LiveBrevoEmailVerificationQaTest extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->smsOtpGateway = new RecordingSmsOtpGateway();
        $this->app->instance(SmsOtpGateway::class, $this->smsOtpGateway);

        $this->whatsAppGateway = new RecordingWhatsAppGateway();
        $this->app->instance(WhatsAppGateway::class, $this->whatsAppGateway);

        $this->withoutVite();
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
    }

    protected RecordingSmsOtpGateway $smsOtpGateway;

    protected RecordingWhatsAppGateway $whatsAppGateway;

    public function createApplication()
    {
        $app = require __DIR__.'/../../bootstrap/app.php';
        $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

        return $app;
    }

    public function test_live_brevo_email_verification_qa_flow(): void
    {
        if (config('mail.default') !== 'smtp') {
            $this->markTestSkipped('MAIL_MAILER must be smtp for live Brevo QA.');
        }

        $inbox = env('MAIL_TEST_TO');
        if (! is_string($inbox) || $inbox === '') {
            $this->markTestSkipped('MAIL_TEST_TO must be set for live Brevo QA.');
        }

        $stamp = (string) time();
        $registerEmail = $this->qaEmail($inbox, 'reg-'.$stamp);
        $password = 'QaVerify1!'.$stamp;

        $this->post(route('register.store'), [
            'name' => 'QA Brevo Verify '.$stamp,
            'username' => 'qabrevo'.$stamp,
            'email' => $registerEmail,
            'password' => $password,
            'password_confirmation' => $password,
            'role' => 'candidate',
            'accept_terms' => true,
        ])->assertRedirect(route('login'));

        $user = User::query()->where('email', $registerEmail)->first();
        $this->assertNotNull($user);
        $this->assertNull($user->email_verified_at);
        $this->assertGuest('account');

        $this->post(route('login.store'), [
            'login' => 'qabrevo'.$stamp,
            'password' => $password,
        ])->assertRedirect();
        $this->assertAuthenticatedAs($user, 'account');

        $verifyUrl = URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), [
            'id' => $user->id,
            'hash' => sha1($user->email),
        ]);

        $this->get($verifyUrl)->assertRedirect(route('account.settings.security'));
        $this->assertNotNull($user->fresh()->email_verified_at);

        $resendEmail = $this->qaEmail($inbox, 'resend-'.$stamp);
        $resendPassword = 'QaResend1!'.$stamp;
        $this->post(route('logout'))->assertRedirect();

        $this->post(route('register.store'), [
            'name' => 'QA Brevo Resend '.$stamp,
            'username' => 'qarend'.$stamp,
            'email' => $resendEmail,
            'password' => $resendPassword,
            'password_confirmation' => $resendPassword,
            'role' => 'candidate',
            'accept_terms' => true,
        ])->assertRedirect(route('login'));

        $resendUser = User::query()->where('email', $resendEmail)->first();
        $this->assertNotNull($resendUser);
        $this->assertNull($resendUser->email_verified_at);

        $this->post(route('login.store'), [
            'login' => 'qarend'.$stamp,
            'password' => $resendPassword,
        ])->assertRedirect();

        for ($i = 0; $i < 6; $i++) {
            $this->actingAs($resendUser, 'account')
                ->post(route('account.verification.send'))
                ->assertRedirect();
        }

        $this->actingAs($resendUser, 'account')
            ->post(route('account.verification.send'))
            ->assertStatus(429);

        $newEmail = $this->qaEmail($inbox, 'change-'.$stamp);
        $this->actingAs($user->fresh(), 'account')
            ->from(route('account.settings.security'))
            ->post(route('account.settings.email.change'), [
                'email' => $newEmail,
            ])
            ->assertRedirect()
            ->assertSessionHas('status');

        $user->refresh();
        $this->assertSame($registerEmail, $user->email);
        $this->assertSame($newEmail, $user->pending_email);

        $changeUrl = URL::temporarySignedRoute('account.settings.email.verify-change', now()->addHours(24), [
            'id' => $user->id,
            'hash' => sha1($newEmail),
        ]);

        $this->actingAs($user, 'account')->get($changeUrl)->assertRedirect(route('account.settings.security'));
        $user->refresh();
        $this->assertSame($newEmail, $user->email);
        $this->assertNull($user->pending_email);
        $this->assertTrue($user->hasVerifiedEmail());

        $this->post(route('logout'))->assertRedirect();
        $this->post(route('password.email'), [
            'email' => $newEmail,
        ])->assertRedirect();

        $this->assertTrue(true);
    }

    private function qaEmail(string $inbox, string $tag): string
    {
        if (! str_contains($inbox, '@')) {
            $this->fail('MAIL_TEST_TO must be a valid email address.');
        }

        [$local, $domain] = explode('@', $inbox, 2);
        $tag = Str::slug($tag, '-');

        return $local.'+'.$tag.'@'.$domain;
    }

    public function actingAs($user, $guard = null)
    {
        if ($guard === null && $user instanceof User) {
            $guard = $user->isAdmin() ? 'admin' : 'account';
        }

        if ($guard === 'admin') {
            $this->app['auth']->guard('account')->logout();
        } elseif ($guard === 'account') {
            $this->app['auth']->guard('admin')->logout();
        }

        return parent::actingAs($user, $guard);
    }
}
