<?php

namespace Tests\Feature;

use App\Enums\CommunicationChannel;
use App\Mail\EmployerRecruitmentMail;
use App\Models\Application;
use App\Models\Job;
use App\Notifications\VerifyAccountEmailNotification;
use App\Services\CommunicationPreferenceService;
use App\Services\EmployerNotificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * REG-COM-004 — employer self-service communication preferences (account settings).
 */
class AccountEmployerCommunicationSelfServiceTest extends TestCase
{
    public function test_eligible_employer_can_access_communication_settings(): void
    {
        $employer = $this->makeEmployer();

        $this->actingAs($employer)
            ->get(route('account.settings.communication'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Account/Settings/Account')
                ->where('role', 'employer')
                ->has('communication_channels', count(CommunicationChannel::cases())));

        $this->actingAs($employer)
            ->get(route('account.settings.account'))
            ->assertRedirect(route('account.company'));
    }

    public function test_employer_sees_persisted_push_and_system_preferences(): void
    {
        $employer = $this->makeEmployer();
        app(CommunicationPreferenceService::class)->sync($employer, [
            CommunicationChannel::Push->value => false,
            CommunicationChannel::System->value => true,
        ], 'test');

        $this->actingAs($employer)
            ->get(route('account.settings.communication'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('communication_channels', fn ($channels) => collect($channels)->firstWhere('value', 'push')['opted_in'] === false
                    && collect($channels)->firstWhere('value', 'system')['opted_in'] === true));
    }

    public function test_employer_can_update_push_and_system_independently_and_persist(): void
    {
        $employer = $this->makeEmployer();

        $this->actingAs($employer)
            ->put(route('account.settings.communication.update'), [
                'channels' => [
                    CommunicationChannel::Push->value => false,
                    CommunicationChannel::System->value => false,
                ],
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertTrue(
            $employer->communicationPreferences()
                ->where('channel', CommunicationChannel::Push->value)
                ->where('opted_in', false)
                ->exists()
        );
        $this->assertTrue(
            $employer->communicationPreferences()
                ->where('channel', CommunicationChannel::System->value)
                ->where('opted_in', false)
                ->exists()
        );

        $this->actingAs($employer)
            ->get(route('account.settings.communication'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('communication_channels', fn ($channels) => collect($channels)->firstWhere('value', 'push')['opted_in'] === false
                    && collect($channels)->firstWhere('value', 'system')['opted_in'] === false));

        $this->actingAs($employer)
            ->put(route('account.settings.communication.update'), [
                'channels' => [
                    CommunicationChannel::Push->value => true,
                    CommunicationChannel::System->value => false,
                ],
            ])
            ->assertRedirect();

        $this->assertTrue(
            $employer->communicationPreferences()
                ->where('channel', CommunicationChannel::Push->value)
                ->where('opted_in', true)
                ->exists()
        );
    }

    public function test_employer_cannot_modify_another_users_preferences_via_request_payload(): void
    {
        $employer = $this->makeEmployer();
        $other = $this->makeEmployer();

        $this->actingAs($employer)
            ->put(route('account.settings.communication.update'), [
                'user_id' => $other->id,
                'channels' => [
                    CommunicationChannel::Push->value => false,
                    CommunicationChannel::System->value => false,
                ],
            ])
            ->assertRedirect();

        $this->assertTrue(
            $employer->communicationPreferences()
                ->where('channel', CommunicationChannel::Push->value)
                ->where('opted_in', false)
                ->exists()
        );
        $this->assertFalse(
            $other->communicationPreferences()
                ->where('channel', CommunicationChannel::Push->value)
                ->where('opted_in', false)
                ->exists()
        );
    }

    public function test_guest_and_non_account_roles_cannot_access_communication_settings(): void
    {
        $this->get(route('account.settings.communication'))->assertRedirect(route('login'));
        $this->put(route('account.settings.communication.update'), [
            'channels' => [CommunicationChannel::Push->value => false],
        ])->assertRedirect(route('login'));

        $this->actingAs($this->makeAdmin())
            ->get(route('account.settings.communication'))
            ->assertRedirect(route('login'));
    }

    public function test_missing_preference_rows_receive_established_defaults_on_load(): void
    {
        $employer = $this->makeEmployer();
        $employer->communicationPreferences()->delete();
        $this->assertSame(0, $employer->communicationPreferences()->count());

        $this->actingAs($employer)
            ->get(route('account.settings.communication'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('communication_channels', fn ($channels) => collect($channels)->firstWhere('value', 'push')['opted_in'] === true
                    && collect($channels)->firstWhere('value', 'system')['opted_in'] === true
                    && collect($channels)->firstWhere('value', 'marketing_email')['opted_in'] === false));

        $this->assertGreaterThan(0, $employer->fresh()->communicationPreferences()->count());
    }

    public function test_candidate_communication_settings_remain_available_after_employer_access_enabled(): void
    {
        $candidate = $this->makeCandidate();

        $this->actingAs($candidate)
            ->get(route('account.settings.account'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('role', 'candidate'));

        $this->actingAs($candidate)
            ->put(route('account.settings.communication.update'), [
                'channels' => [CommunicationChannel::MarketingEmail->value => true],
            ])
            ->assertRedirect();
    }

    public function test_employer_self_service_push_and_system_affect_respective_delivery_channels(): void
    {
        Mail::fake();
        $employer = $this->makeEmployer([
            'email' => 'com004-delivery@example.com',
            'email_verified_at' => now(),
            'mobile_verified_at' => now(),
        ]);

        $this->actingAs($employer)
            ->put(route('account.settings.communication.update'), [
                'channels' => [
                    CommunicationChannel::System->value => false,
                    CommunicationChannel::Push->value => true,
                ],
            ])
            ->assertRedirect();

        $job = Job::factory()->create(['employer_id' => $employer->employerProfile?->id]);
        $application = Application::factory()->create(['job_id' => $job->id]);

        DB::transaction(fn () => app(EmployerNotificationService::class)->notifyApplicationReceived($application));

        Mail::assertNothingQueued();
        $this->assertSame(1, $employer->fresh()->unreadNotifications()->count());

        Mail::fake();
        $this->actingAs($employer)
            ->put(route('account.settings.communication.update'), [
                'channels' => [
                    CommunicationChannel::System->value => true,
                    CommunicationChannel::Push->value => false,
                ],
            ])
            ->assertRedirect();

        $applicationTwo = Application::factory()->create(['job_id' => $job->id]);
        DB::transaction(fn () => app(EmployerNotificationService::class)->notifyApplicationReceived($applicationTwo));

        Mail::assertQueued(EmployerRecruitmentMail::class, 1);
        $this->assertSame(1, $employer->fresh()->unreadNotifications()->count());
    }

    public function test_verification_email_still_sent_when_system_opted_out(): void
    {
        Notification::fake();
        $employer = $this->makeEmployer([
            'email' => 'com004-verify@example.com',
            'email_verified_at' => null,
        ]);

        app(CommunicationPreferenceService::class)->sync($employer, [
            CommunicationChannel::System->value => false,
        ], 'test');

        $employer->sendEmailVerificationNotification();

        Notification::assertSentTo($employer, VerifyAccountEmailNotification::class);
    }
}
