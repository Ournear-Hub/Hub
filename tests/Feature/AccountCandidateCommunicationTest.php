<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Enums\CandidateNotificationEvent;
use App\Enums\CommunicationChannel;
use App\Enums\ConversationContextType;
use App\Models\Application;
use App\Models\Conversation;
use App\Models\Job;
use App\Models\User;
use App\Notifications\CandidateLifecycleNotification;
use App\Services\ApplicationPipelineService;
use App\Services\MessageService;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AccountCandidateCommunicationTest extends TestCase
{
    public function test_guest_notifications_and_communication_settings_redirect_to_login(): void
    {
        $this->get(route('account.notifications.index'))->assertRedirect(route('login'));
        $this->post(route('account.notifications.read-all'))->assertRedirect(route('login'));
        $this->get(route('account.settings.communication'))->assertRedirect(route('login'));
    }

    public function test_guest_application_does_not_create_a_candidate_notification(): void
    {
        $job = Job::factory()->create();

        $this->post(route('jobs.apply.store', $job))->assertRedirect(route('login'));

        $this->assertNull(Application::query()->where('email', 'v12f.guest@example.com')->first());
        $this->assertSame(0, DatabaseNotification::query()->where('type', CandidateLifecycleNotification::class)->count());
    }

    public function test_candidate_application_creates_a_user_owned_notification(): void
    {
        $candidate = $this->makeCandidate();
        $job = Job::factory()->create(['title' => 'Site Engineer']);

        $this->applyAsCandidate($candidate, $job);

        $notification = $candidate->notifications()->first();
        $this->assertNotNull($notification);
        $this->assertNull($notification->read_at);
        $this->assertSame(CandidateNotificationEvent::ApplicationReceived->value, $notification->data['event']);
        $this->assertSame('/account/applications', $notification->data['url']);
        $this->assertStringContainsString('Site Engineer', (string) $notification->data['body']);
        $this->assertArrayNotHasKey('resume_path', $notification->data);
        $this->assertSame(1, $candidate->unreadNotifications()->count());
    }

    public function test_candidate_notification_list_unread_count_and_mark_read_persist(): void
    {
        $candidate = $this->makeCandidate();
        $this->applyAsCandidate($candidate, Job::factory()->create());

        $this->actingAs($candidate)
            ->get(route('account.notifications.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Account/Notifications')
                ->where('unread_count', 1)
                ->has('notifications.data', 1)
                ->where('notifications.data.0.is_unread', true)
                ->where('notifications.data.0.url', '/account/applications')
                ->missing('notifications.data.0.context')
                ->missing('notifications.data.0.resume_path'));

        $notification = $candidate->notifications()->first();
        $this->assertNotNull($notification);

        $this->actingAs($candidate)
            ->post(route('account.notifications.read', $notification))
            ->assertRedirect();

        $this->assertNotNull($notification->fresh()->read_at);

        $this->actingAs($candidate)
            ->get(route('account.notifications.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('unread_count', 0)
                ->where('notifications.data.0.is_unread', false));
    }

    public function test_mark_all_read_clears_unread_count(): void
    {
        $candidate = $this->makeCandidate();
        $this->applyAsCandidate($candidate, Job::factory()->create());
        Mail::fake();
        $application = Application::query()->where('user_id', $candidate->id)->first();
        $this->assertNotNull($application);
        app(ApplicationPipelineService::class)->changeStatus($application, ApplicationStatus::Rejected, $this->makeAdmin());

        $this->assertSame(2, $candidate->fresh()->unreadNotifications()->count());

        $this->actingAs($candidate)
            ->post(route('account.notifications.read-all'))
            ->assertRedirect();

        $this->assertSame(0, $candidate->fresh()->unreadNotifications()->count());
    }

    public function test_status_change_notifies_only_the_owning_candidate_and_skips_duplicates(): void
    {
        Mail::fake();
        $owner = $this->makeCandidate(['name' => 'Owner Candidate']);
        $other = $this->makeCandidate(['name' => 'Other Candidate']);
        $employer = $this->makeEmployer();
        $admin = $this->makeAdmin();
        $application = Application::factory()->create([
            'user_id' => $owner->id,
            'email' => $owner->email,
            'full_name' => $owner->name,
            'status' => ApplicationStatus::New,
        ]);

        $pipeline = app(ApplicationPipelineService::class);
        $pipeline->changeStatus($application, ApplicationStatus::Reviewing, $admin);
        $pipeline->changeStatus($application->fresh(), ApplicationStatus::Reviewing, $admin);

        $this->assertSame(1, $owner->notifications()->where('type', CandidateLifecycleNotification::class)->count());
        $this->assertSame(0, $other->notifications()->count());
        $this->assertSame(0, $employer->notifications()->count());
        $this->assertSame(
            CandidateNotificationEvent::ApplicationStatusChanged->value,
            $owner->notifications()->first()?->data['event']
        );

        $pipeline->shortlist($application->fresh(), $admin);
        $this->assertSame(
            1,
            $owner->notifications()
                ->where('data->event', CandidateNotificationEvent::ApplicationShortlisted->value)
                ->count()
        );
    }

    public function test_withdrawn_status_does_not_notify_the_candidate(): void
    {
        $candidate = $this->makeCandidate();
        $application = Application::factory()->create([
            'user_id' => $candidate->id,
            'email' => $candidate->email,
            'status' => ApplicationStatus::New,
        ]);

        $this->actingAs($candidate)
            ->patch(route('account.applications.withdraw', $application))
            ->assertRedirect();

        $this->assertSame(ApplicationStatus::Withdrawn, $application->fresh()->status);
        $this->assertSame(0, $candidate->notifications()->count());
    }

    public function test_candidate_cannot_see_or_mark_another_candidates_notification(): void
    {
        $owner = $this->makeCandidate();
        $other = $this->makeCandidate();
        $this->applyAsCandidate($owner, Job::factory()->create());

        $notification = $owner->notifications()->first();
        $this->assertNotNull($notification);

        $this->actingAs($other)
            ->get(route('account.notifications.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('unread_count', 0)
                ->has('notifications.data', 0));

        $this->actingAs($other)
            ->post(route('account.notifications.read', $notification))
            ->assertForbidden();
        $this->assertNull($notification->fresh()->read_at);
    }

    public function test_employer_and_admin_cannot_use_candidate_notifications(): void
    {
        $this->actingAs($this->makeEmployer())->get(route('account.notifications.index'))->assertOk();
        $this->actingAs($this->makeEmployer())->get(route('account.settings.communication'))->assertForbidden();
        $this->actingAs($this->makeAdmin())->get(route('account.notifications.index'))->assertRedirect(route('login'));
        $this->actingAs($this->makeAdmin())->get(route('admin.notifications.index'))->assertOk();
        $this->actingAs($this->makeAdmin())->get(route('admin.messages.index'))->assertOk();
    }

    public function test_candidate_can_load_and_update_own_communication_preferences_only(): void
    {
        $candidate = $this->makeCandidate();
        $other = $this->makeCandidate();

        $this->actingAs($candidate)
            ->get(route('account.settings.communication'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Account/Settings/Communication')
                ->has('channels', count(CommunicationChannel::cases())));

        $this->actingAs($candidate)
            ->put(route('account.settings.communication.update'), [
                'user_id' => $other->id,
                'channels' => [
                    CommunicationChannel::MarketingEmail->value => true,
                    CommunicationChannel::MarketingSms->value => false,
                    CommunicationChannel::Whatsapp->value => false,
                    CommunicationChannel::Push->value => false,
                    CommunicationChannel::System->value => true,
                ],
            ])
            ->assertRedirect();

        $this->assertTrue(
            $candidate->communicationPreferences()
                ->where('channel', CommunicationChannel::MarketingEmail->value)
                ->where('opted_in', true)
                ->exists()
        );
        $this->assertFalse(
            $other->communicationPreferences()
                ->where('channel', CommunicationChannel::MarketingEmail->value)
                ->where('opted_in', true)
                ->exists()
        );
    }

    public function test_dashboard_exposes_unread_count_without_message_nav(): void
    {
        $candidate = $this->makeCandidate();
        $this->applyAsCandidate($candidate, Job::factory()->create());

        $response = $this->actingAs($candidate)
            ->get(route('account.home'))
            ->assertOk();

        $expectedUnread = $candidate->fresh()->unreadNotifications()->count();
        $this->assertGreaterThan(0, $expectedUnread);

        $response->assertInertia(fn (Assert $page) => $page
            ->component('Account/Home')
            ->where('accountAlerts.unread', $expectedUnread)
            ->where('dashboard.applications.count', 1));

        $this->assertFalse(Route::has('account.messages.index'));
    }

    public function test_candidate_cannot_access_internal_staff_conversations(): void
    {
        $sender = $this->makeAdmin(['email' => 'v12f.sender@example.com']);
        $recipient = $this->makeAdmin(['email' => 'v12f.recipient@example.com']);
        $candidate = $this->makeCandidate();
        $conversation = app(MessageService::class)->start(
            $sender,
            $recipient,
            'Internal only',
            'Staff note',
            ConversationContextType::Application,
            Application::factory()->create(['user_id' => $candidate->id])->id,
        );

        $this->assertTrue($conversation->hasParticipant($sender));
        $this->assertFalse($conversation->hasParticipant($candidate));
        $this->actingAs($candidate)->get(route('admin.messages.index'))->assertForbidden();
        $this->assertSame(1, Conversation::query()->count());
    }

    public function test_admin_notifications_still_work_alongside_candidate_notifications(): void
    {
        $admin = $this->makeAdmin();
        $candidate = $this->makeCandidate();
        $job = Job::factory()->create();

        $this->applyAsCandidate($candidate, $job);

        $this->assertSame(1, $candidate->notifications()->where('type', CandidateLifecycleNotification::class)->count());
        $this->assertSame(1, $admin->notifications()->count());
        $this->actingAs($admin)
            ->get(route('admin.notifications.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Notifications/Index')
                ->where('unreadCount', 1));
    }

}
