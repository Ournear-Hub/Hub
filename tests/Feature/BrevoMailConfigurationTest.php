<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class BrevoMailConfigurationTest extends TestCase
{
    public function test_smtp_mailer_exposes_standard_environment_driven_settings(): void
    {
        $smtp = config('mail.mailers.smtp');

        $this->assertSame('smtp', $smtp['transport']);
        $this->assertArrayHasKey('host', $smtp);
        $this->assertArrayHasKey('port', $smtp);
        $this->assertArrayHasKey('username', $smtp);
        $this->assertArrayHasKey('password', $smtp);
        $this->assertArrayHasKey('scheme', $smtp);
    }

    public function test_brevo_smtp_handoff_when_credentials_are_configured(): void
    {
        if (config('mail.default') !== 'smtp') {
            $this->markTestSkipped('Set MAIL_MAILER=smtp in .env and run with phpunit.brevo-live.xml for live Brevo SMTP handoff.');
        }

        $username = config('mail.mailers.smtp.username');
        $password = config('mail.mailers.smtp.password');
        if (! is_string($username) || $username === '' || ! is_string($password) || $password === '') {
            $this->markTestSkipped('Set MAIL_USERNAME and MAIL_PASSWORD (Brevo SMTP key) in .env.');
        }

        $recipient = env('MAIL_TEST_TO');
        if (! is_string($recipient) || $recipient === '') {
            $this->markTestSkipped('Set MAIL_TEST_TO in .env to receive the SMTP probe message.');
        }

        Mail::raw('OurNear Hire SMTP configuration probe.', function ($message) use ($recipient): void {
            $message->to($recipient)->subject('OurNear Hire SMTP probe');
        });

        $this->assertTrue(true);
    }
}
