<?php

namespace Tests\Feature;

use App\Enums\AdminNotificationEvent;
use App\Enums\ApplicationStatus;
use App\Enums\ConversationContextType;
use App\Enums\InvoiceStatus;
use App\Enums\PlacementStatus;
use App\Models\Application;
use App\Models\Conversation;
use App\Models\EmployerSubmission;
use App\Models\Invoice;
use App\Models\Job;
use App\Models\Message;
use App\Models\Placement;
use App\Models\User;
use App\Services\AdminAlertService;
use Illuminate\Notifications\DatabaseNotification;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CommunicationLifecycleTest extends TestCase
{
    public function test_application_received_notifies_admins_and_get_pages_do_not_create_notifications(): void
    {
        $admin = $this->makeAdmin();
        $job = Job::factory()->create(['title' => 'Site Engineer']);

        $this->applyAsCandidate($this->makeCandidate(['email' => 'guest@example.com']), $job);

        $application = Application::query()->where('email', 'guest@example.com')->first();
        $this->assertNotNull($application);
        $this->assertSame(ApplicationStatus::New, $application->status);

        $notification = $admin->notifications()->first();
        $this->assertNotNull($notification);
        $this->assertNull($notification->read_at);
        $this->assertSame(AdminNotificationEvent::ApplicationReceived->value, $notification->data['event']);
        $this->assertSame('/admin/applications?application='.$application->id, $notification->data['url']);
        $this->assertSame(1, $admin->notifications()->count());

        $this->actingAs($admin)->get(route('admin.applications.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.notifications.index'))->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Notifications/Index')
                ->where('unreadCount', 1)
                ->where('notifications.data.0.event', AdminNotificationEvent::ApplicationReceived->value));
        $this->assertSame(1, $admin->notifications()->count());
        $this->assertSame(ApplicationStatus::New, $application->fresh()->status);
    }

    public function test_shortlist_notifies_once_and_mark_read_does_not_change_application_status(): void
    {
        $admin = $this->makeAdmin();
        $job = Job::factory()->create();
        $application = Application::factory()->create([
            'job_id' => $job->id,
            'full_name' => 'Asha Candidate',
            'status' => ApplicationStatus::New,
        ]);

        $this->actingAs($admin)->post(route('admin.applications.shortlist', $application))->assertRedirect();
        $this->actingAs($admin)
            ->from(route('admin.applications.index'))
            ->post(route('admin.applications.shortlist', $application))
            ->assertSessionHasErrors('decision');

        $this->assertSame(1, $admin->notifications()
            ->where('data->event', AdminNotificationEvent::CandidateShortlisted->value)
            ->count());
        $this->assertSame(ApplicationStatus::Shortlisted, $application->fresh()->status);

        $notification = $admin->notifications()
            ->where('data->event', AdminNotificationEvent::CandidateShortlisted->value)
            ->first();
        $this->actingAs($admin)
            ->from(route('admin.notifications.index'))
            ->post(route('admin.notifications.read', $notification))
            ->assertRedirect();
        $this->assertNotNull($notification->fresh()->read_at);
        $this->assertSame(ApplicationStatus::Shortlisted, $application->fresh()->status);

        $this->actingAs($admin)
            ->from(route('admin.notifications.index'))
            ->post(route('admin.notifications.unread', $notification))
            ->assertRedirect();
        $this->assertNull($notification->fresh()->read_at);

        $this->actingAs($admin)
            ->from(route('admin.notifications.index'))
            ->post(route('admin.notifications.read-all'))
            ->assertRedirect();
        $this->assertSame(0, $admin->unreadNotifications()->count());
        $this->assertSame(ApplicationStatus::Shortlisted, $application->fresh()->status);
    }

    public function test_duplicate_alert_fingerprint_is_not_stored_twice(): void
    {
        $admin = $this->makeAdmin();
        $application = Application::factory()->create(['full_name' => 'Repeat']);
        $alerts = app(AdminAlertService::class);
        $alerts->applicationReceived($application);
        $alerts->applicationReceived($application);

        $this->assertSame(1, $admin->notifications()->count());
        $this->assertSame(1, DatabaseNotification::query()->count());
    }

    public function test_invoice_issue_notifies_admin_and_read_state_does_not_change_invoice(): void
    {
        $admin = $this->makeAdmin();
        [, , , , , $employee] = $this->joinedEmployee();
        $this->actingAs($admin)->post(route('admin.employees.invoices.store', $employee), [
            'amount' => '100000',
            'gst_rate' => '0',
            'billed_at' => now()->toDateString(),
            'due_at' => now()->addDays(15)->toDateString(),
        ]);
        $invoice = Invoice::query()->where('employee_id', $employee->id)->first();
        $beforeIssue = $admin->notifications()->count();
        $this->actingAs($admin)->from(route('admin.finance.index'))->post(route('admin.invoices.issue', $invoice));

        $this->assertSame(InvoiceStatus::Issued, $invoice->fresh()->status);
        $this->assertSame($beforeIssue + 1, $admin->notifications()->count());
        $issued = $admin->notifications()->where('data->event', AdminNotificationEvent::InvoiceIssued->value)->first();
        $this->assertNotNull($issued);

        $this->actingAs($admin)->post(route('admin.notifications.read', $issued));
        $this->assertSame(InvoiceStatus::Issued, $invoice->fresh()->status);
        $this->assertSame(PlacementStatus::Joined, $employee->placement->fresh()->status);
    }

    public function test_admin_can_message_another_admin_with_context_and_cannot_message_candidates(): void
    {
        $sender = $this->makeAdmin(['name' => 'Sender Admin', 'email' => 'sender@example.com']);
        $recipient = $this->makeAdmin(['name' => 'Recipient Admin', 'email' => 'recipient@example.com']);
        $candidate = $this->makeCandidate();
        $application = Application::factory()->create(['full_name' => 'Context Candidate']);

        $this->actingAs($sender)
            ->from(route('admin.messages.index'))
            ->post(route('admin.messages.store'), [
                'recipient_id' => $candidate->id,
                'subject' => 'Hello candidate',
                'body' => 'This should not be an in-app portal message.',
            ])
            ->assertSessionHasErrors('recipient_id');
        $this->assertSame(0, Conversation::query()->count());

        $this->actingAs($sender)
            ->post(route('admin.messages.store'), [
                'recipient_id' => $recipient->id,
                'subject' => 'Replacement follow-up',
                'body' => 'Please review this application.',
                'context_type' => ConversationContextType::Application->value,
                'context_id' => $application->id,
            ])
            ->assertRedirect();

        $conversation = Conversation::query()->first();
        $this->assertNotNull($conversation);
        $this->assertSame('Replacement follow-up', $conversation->subject);
        $this->assertSame(ConversationContextType::Application, $conversation->context_type);
        $this->assertSame($application->id, $conversation->context_id);
        $this->assertSame(1, $conversation->messages()->count());
        $this->assertTrue($conversation->isUnreadFor($recipient));
        $this->assertFalse($conversation->isUnreadFor($sender));

        $this->actingAs($recipient)
            ->get(route('admin.messages.index', ['conversation' => $conversation->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Messages/Index')
                ->where('selected.subject', 'Replacement follow-up')
                ->where('selected.messages.0.body', 'Please review this application.'));

        $this->assertFalse($conversation->fresh()->isUnreadFor($recipient));

        $this->actingAs($recipient)
            ->from(route('admin.messages.index', ['conversation' => $conversation->id]))
            ->post(route('admin.messages.reply', $conversation), ['body' => 'Reviewed and noted.'])
            ->assertRedirect();

        $this->assertSame(2, Message::query()->count());
        $this->assertTrue($conversation->fresh()->load(['latestMessage', 'participantRecords'])->isUnreadFor($sender));
        $this->assertSame(2, $conversation->messages()->count());

        $this->actingAs($candidate)->get(route('admin.messages.index'))->assertForbidden();
        $this->actingAs($this->makeEmployer())->post(route('admin.messages.store'), [
            'recipient_id' => $sender->id,
            'subject' => 'Nope',
            'body' => 'Not allowed',
        ])->assertForbidden();
    }

    public function test_missing_admin_recipients_do_not_break_application_submission(): void
    {
        $this->assertSame(0, User::query()->where('role', 'admin')->count());
        $job = Job::factory()->create();

        $this->applyAsCandidate($this->makeCandidate(['email' => 'solo@example.com']), $job);

        $this->assertSame(1, Application::query()->count());
        $this->assertSame(0, User::query()->where('role', 'admin')->count());
        $this->assertSame(
            0,
            DatabaseNotification::query()
                ->where('type', '!=', \App\Notifications\CandidateLifecycleNotification::class)
                ->count()
        );
    }

    /**
     * @return array{0: \App\Models\EmployerProfile, 1: Job, 2: Application, 3: EmployerSubmission, 4: Placement, 5: \App\Models\Employee}
     */
    private function joinedEmployee(): array
    {
        $admin = $this->makeAdmin();
        $employer = $this->makeEmployer();
        $profile = $employer->employerProfile;
        $this->assertNotNull($profile);
        $job = Job::factory()->create(['employer_id' => $profile->id, 'title' => 'Site Engineer']);
        $candidate = $this->makeCandidate(['name' => 'Asha Candidate']);
        $application = Application::factory()->create([
            'job_id' => $job->id,
            'user_id' => $candidate->id,
            'full_name' => 'Asha Candidate',
            'status' => ApplicationStatus::Shortlisted,
        ]);

        $this->actingAs($admin)->post(route('admin.applications.submit', $application));
        $submission = EmployerSubmission::query()->where('application_id', $application->id)->first();
        $this->actingAs($admin)->post(route('admin.submissions.select', $submission));
        $this->actingAs($admin)->post(route('admin.submissions.place', $submission->fresh()));
        $placement = Placement::query()->where('employer_submission_id', $submission->id)->first();
        $this->actingAs($admin)->post(route('admin.placements.offer', $placement), [
            'offered_at' => now()->toDateString(),
        ]);
        $this->actingAs($admin)->post(route('admin.placements.accept', $placement));
        $this->actingAs($admin)->post(route('admin.placements.confirm-joining', $placement), [
            'expected_joining_date' => now()->toDateString(),
        ]);
        $this->actingAs($admin)->post(route('admin.placements.join', $placement), [
            'actual_joining_date' => now()->toDateString(),
        ]);

        $placement->refresh();

        return [$profile, $job, $application, $submission->fresh(), $placement, $placement->employee];
    }
}
