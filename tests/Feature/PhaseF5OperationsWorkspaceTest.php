<?php

namespace Tests\Feature;

use App\Enums\AdminNotificationEvent;
use App\Enums\ApplicationStatus;
use App\Enums\ConversationContextType;
use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Enums\StaffRoleSlug;
use App\Models\Application;
use App\Models\Conversation;
use App\Models\Employee;
use App\Models\EmployerSubmission;
use App\Models\Invoice;
use App\Models\Job;
use App\Models\Payment;
use App\Models\Placement;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PhaseF5OperationsWorkspaceTest extends TestCase
{
    public function test_finance_dashboard_uses_real_counts_and_zero_empty_state(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->get(route('admin.finance.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Finance/Index')
                ->where('dashboard.total_invoices', 0)
                ->where('dashboard.pending', 0)
                ->where('dashboard.paid', 0)
                ->where('dashboard.failed', 0)
                ->where('dashboard.failed_available', false)
                ->where('dashboard.refunded', 0)
                ->where('dashboard.refunded_available', false)
                ->where('dashboard.cancelled', 0)
                ->where('dashboard.total_invoiced_amount', '0.00')
                ->where('dashboard.total_paid_amount', '0.00')
                ->where('dashboard.total_pending_amount', '0.00')
                ->where('dashboard.gateway_configured', false)
                ->where('dashboard.gateway_message', 'Gateway integration not configured')
                ->where('invoices.data', [])
                ->where('payments.data', [])
                ->where('can_manage', true));
    }

    public function test_invoice_and_payment_lists_expose_stored_relationships_and_filters(): void
    {
        $admin = $this->makeAdmin();
        [$profile, $job, $application, , $placement, $employee] = $this->joinedEmployee();
        $candidate = $application->user?->candidateProfile;
        $this->assertNotNull($candidate);

        $this->actingAs($admin)->post(route('admin.employees.invoices.store', $employee), $this->invoicePayload([
            'amount' => '100000',
            'gst_rate' => '0',
        ]));
        $invoice = Invoice::query()->where('employee_id', $employee->id)->first();
        $this->assertNotNull($invoice);
        $this->actingAs($admin)->post(route('admin.invoices.issue', $invoice));
        $this->actingAs($admin)->post(route('admin.invoices.payments.store', $invoice), $this->paymentPayload([
            'amount' => '40000',
            'reference' => 'NEFT-F5',
        ]));
        $payment = Payment::query()->where('invoice_id', $invoice->id)->first();
        $this->assertNotNull($payment);
        $invoice->refresh();
        $this->assertSame(InvoiceStatus::PartiallyPaid, $invoice->status);

        $this->actingAs($admin)
            ->get(route('admin.finance.index', [
                'candidate_id' => $candidate->id,
                'employer_id' => $profile->id,
                'job_id' => $job->id,
                'status' => InvoiceStatus::PartiallyPaid->value,
                'invoice' => $invoice->id,
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Finance/Index')
                ->has('invoices.data', 1)
                ->where('invoices.data.0.business_id', $invoice->business_id)
                ->where('invoices.data.0.candidate.name', 'Asha Candidate')
                ->where('invoices.data.0.application.business_id', $application->business_id)
                ->where('invoices.data.0.placement.business_id', $placement->business_id)
                ->where('invoices.data.0.invoice_type', 'Recruitment fee')
                ->where('invoices.data.0.status', InvoiceStatus::PartiallyPaid->value)
                ->where('dashboard.total_invoices', 1)
                ->where('dashboard.pending', 1)
                ->where('dashboard.paid', 0)
                ->where('dashboard.total_invoiced_amount', '100000.00')
                ->where('dashboard.total_paid_amount', '40000.00')
                ->where('dashboard.total_pending_amount', '60000.00')
                ->where('selected.id', $invoice->id)
                ->where('selected.status', InvoiceStatus::PartiallyPaid->value)
                ->where('selected.chain.0.key', 'invoice'));

        $this->actingAs($admin)
            ->get(route('admin.finance.index', [
                'view' => 'payments',
                'payment' => $payment->id,
                'candidate_id' => $candidate->id,
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('payments.data', 1)
                ->where('payments.data.0.business_id', $payment->business_id)
                ->where('payments.data.0.status', 'Recorded')
                ->where('payments.data.0.gateway_configured', false)
                ->where('payments.data.0.gateway_message', 'Gateway integration not configured')
                ->where('payments.data.0.gateway_reference', null)
                ->where('payments.data.0.reference', 'NEFT-F5')
                ->where('selectedPayment.id', $payment->id)
                ->where('selectedPayment.status', 'Recorded')
                ->where('selectedPayment.invoice_business_id', $invoice->business_id)
                ->where('selectedPayment.candidate.name', 'Asha Candidate')
                ->where('selectedPayment.application.business_id', $application->business_id)
                ->where('selectedPayment.chain.1.key', 'payment'));
    }

    public function test_opening_finance_records_does_not_mark_a_payment_paid(): void
    {
        $admin = $this->makeAdmin();
        [, , , , , $employee] = $this->joinedEmployee();
        $this->actingAs($admin)->post(route('admin.employees.invoices.store', $employee), $this->invoicePayload());
        $invoice = Invoice::query()->where('employee_id', $employee->id)->first();
        $this->actingAs($admin)->post(route('admin.invoices.issue', $invoice));
        $invoice->refresh();
        $this->assertSame(InvoiceStatus::Issued, $invoice->status);
        $this->assertSame(0, Payment::query()->count());

        $this->actingAs($admin)->get(route('admin.finance.index', ['invoice' => $invoice->id]))->assertOk();
        $this->actingAs($admin)->get(route('admin.finance.index', ['view' => 'payments']))->assertOk();

        $invoice->refresh();
        $this->assertSame(InvoiceStatus::Issued, $invoice->status);
        $this->assertSame(0, Payment::query()->count());
        $this->assertSame('100000.00', (string) $invoice->total_amount);
    }

    public function test_unavailable_payment_statuses_do_not_invent_records(): void
    {
        $admin = $this->makeAdmin();
        [, , , , , $employee] = $this->joinedEmployee();
        $this->actingAs($admin)->post(route('admin.employees.invoices.store', $employee), $this->invoicePayload());
        $invoice = Invoice::query()->where('employee_id', $employee->id)->first();
        $this->actingAs($admin)->post(route('admin.invoices.issue', $invoice));
        $this->actingAs($admin)->post(route('admin.invoices.payments.store', $invoice), $this->paymentPayload(['amount' => '100000']));

        $this->actingAs($admin)
            ->get(route('admin.finance.index', ['view' => 'payments', 'payment_status' => 'Failed']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('payments.data', [])
                ->where('dashboard.failed', 0)
                ->where('dashboard.refunded', 0));
    }

    public function test_dashboard_pending_payments_match_issued_and_partially_paid_invoices(): void
    {
        $admin = $this->makeAdmin();
        [, , , , , $employee] = $this->joinedEmployee();
        $this->actingAs($admin)->post(route('admin.employees.invoices.store', $employee), $this->invoicePayload());
        $invoice = Invoice::query()->where('employee_id', $employee->id)->first();
        $this->actingAs($admin)->post(route('admin.invoices.issue', $invoice));

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('stats.pending_actions', function ($actions) {
                    $payments = collect($actions)->firstWhere('key', 'payments');

                    return is_array($payments) && $payments['value'] === 1;
                }));
    }

    public function test_finance_rbac_preserves_existing_permissions(): void
    {
        $admin = $this->makeAdmin();
        [, , , , , $employee] = $this->joinedEmployee();
        $this->actingAs($admin)->post(route('admin.employees.invoices.store', $employee), $this->invoicePayload());
        $invoice = Invoice::query()->where('employee_id', $employee->id)->first();

        $this->actingAs($this->makeStaff(StaffRoleSlug::Recruiter->value))
            ->get(route('admin.finance.index'))
            ->assertForbidden();

        $viewer = $this->makeStaff(StaffRoleSlug::Viewer->value);
        $this->actingAs($viewer)
            ->get(route('admin.finance.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('can_manage', false));
        $this->actingAs($viewer)
            ->post(route('admin.invoices.issue', $invoice))
            ->assertForbidden();

        $finance = $this->makeStaff(StaffRoleSlug::Finance->value);
        $this->actingAs($finance)
            ->get(route('admin.finance.index', ['invoice' => $invoice->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('can_manage', true));
        $this->actingAs($finance)->post(route('admin.invoices.issue', $invoice))->assertRedirect();
        $this->assertSame(InvoiceStatus::Issued, $invoice->fresh()->status);
    }

    public function test_notification_list_read_unread_detail_and_filtering(): void
    {
        $admin = $this->makeAdmin();
        $job = Job::factory()->create(['title' => 'Site Engineer']);
        $candidate = $this->makeCandidate(['email' => 'f5-guest@example.com']);
        $this->applyAsCandidate($candidate, $job);
        $application = Application::query()->where('email', 'f5-guest@example.com')->first();
        $this->assertNotNull($application);

        $this->actingAs($admin)
            ->get(route('admin.notifications.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Notifications/Index')
                ->where('unreadCount', 1)
                ->where('notifications.data.0.event', AdminNotificationEvent::ApplicationReceived->value)
                ->where('notifications.data.0.category', 'Application')
                ->where('notifications.data.0.is_unread', true)
                ->where('notifications.data.0.recipient.id', $admin->id)
                ->has('categories', 17)
                ->where('categories.0', 'Application')
                ->where('categories.1', 'Freelancer')
                ->where('categories.9', 'Payment')
                ->where('categories.10', 'Interview')
                ->where('categories.11', 'Task')
                ->where('categories.12', 'CRM')
                ->where('categories.13', 'Partner')
                ->where('categories.14', 'Investor')
                ->where('categories.15', 'Campaign')
                ->where('categories.16', 'Automation'));

        $this->actingAs($admin)
            ->get(route('admin.notifications.index', ['category' => 'Payment']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('notifications.data', []));

        $notification = $admin->notifications()->first();
        $this->actingAs($admin)
            ->get(route('admin.notifications.index', ['notification' => $notification->id, 'read_state' => 'unread']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('selected.id', $notification->id)
                ->where('selected.related.application.business_id', $application->business_id)
                ->where('selected.can_toggle_read', true));

        $this->actingAs($admin)->post(route('admin.notifications.read', $notification))->assertRedirect();
        $this->assertNotNull($notification->fresh()->read_at);
        $this->assertSame(ApplicationStatus::New, $application->fresh()->status);
        $this->assertSame(0, $admin->fresh()->unreadNotifications()->count());

        $this->actingAs($admin)
            ->get(route('admin.notifications.index', ['read_state' => 'read']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('notifications.data', 1)
                ->where('notifications.data.0.is_unread', false));
    }

    public function test_notification_rbac_and_read_actions_do_not_bypass_permissions(): void
    {
        $admin = $this->makeAdmin();
        $job = Job::factory()->create();
        $this->applyAsCandidate($this->makeCandidate(['email' => 'f5-rbac@example.com']), $job);
        $notification = $admin->notifications()->first();
        $this->assertNotNull($notification);

        $this->actingAs($this->makeCandidate())
            ->get(route('admin.notifications.index'))
            ->assertForbidden();

        $this->actingAs($this->makeStaff(StaffRoleSlug::Viewer->value))
            ->get(route('admin.notifications.index'))
            ->assertOk();

        $this->actingAs($this->makeStaff(StaffRoleSlug::Recruiter->value))
            ->post(route('admin.notifications.read', $notification))
            ->assertForbidden();
    }

    public function test_messages_list_detail_context_reply_and_read_behavior(): void
    {
        $sender = $this->makeAdmin(['name' => 'Sender Admin', 'email' => 'f5-sender@example.com']);
        $recipient = $this->makeAdmin(['name' => 'Recipient Admin', 'email' => 'f5-recipient@example.com']);
        $application = Application::factory()->create(['full_name' => 'Context Candidate']);

        $this->actingAs($sender)
            ->post(route('admin.messages.store'), [
                'recipient_id' => $recipient->id,
                'subject' => 'F5 follow-up',
                'body' => 'Please review this application.',
                'context_type' => ConversationContextType::Application->value,
                'context_id' => $application->id,
            ])
            ->assertRedirect();

        $conversation = Conversation::query()->first();
        $this->assertNotNull($conversation);
        $this->assertTrue($conversation->isUnreadFor($recipient));

        $this->actingAs($recipient)
            ->get(route('admin.messages.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Messages/Index')
                ->has('conversations.data', 1)
                ->where('conversations.data.0.subject', 'F5 follow-up')
                ->where('conversations.data.0.unread_count', 1)
                ->where('conversations.data.0.context_type', ConversationContextType::Application->value)
                ->where('conversations.data.0.latest_preview', 'Please review this application.'));

        $this->actingAs($recipient)
            ->get(route('admin.messages.index', ['conversation' => $conversation->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('selected.subject', 'F5 follow-up')
                ->where('selected.messages.0.body', 'Please review this application.')
                ->where('selected.messages.0.attachments_available', false)
                ->where('selected.can_reply', true)
                ->where('selected.chain.3.key', 'candidate'));

        $this->assertFalse($conversation->fresh()->load(['latestMessage', 'participantRecords'])->isUnreadFor($recipient));

        $this->actingAs($recipient)
            ->post(route('admin.messages.reply', $conversation), ['body' => 'Reviewed.'])
            ->assertRedirect();
        $this->assertSame(2, $conversation->messages()->count());
        $this->assertTrue($conversation->fresh()->load(['latestMessage', 'participantRecords'])->isUnreadFor($sender));
    }

    public function test_message_rbac_respects_existing_permissions(): void
    {
        $admin = $this->makeAdmin();
        $other = $this->makeAdmin(['email' => 'f5-other@example.com']);
        $this->actingAs($admin)->post(route('admin.messages.store'), [
            'recipient_id' => $other->id,
            'subject' => 'Private',
            'body' => 'Internal only',
        ]);

        $this->actingAs($this->makeStaff(StaffRoleSlug::Viewer->value))
            ->get(route('admin.messages.index'))
            ->assertForbidden();
        $this->actingAs($this->makeStaff(StaffRoleSlug::Finance->value))
            ->get(route('admin.messages.index'))
            ->assertForbidden();
        $this->actingAs($this->makeStaff(StaffRoleSlug::Recruiter->value))
            ->get(route('admin.messages.index'))
            ->assertOk();
        $this->actingAs($this->makeCandidate())
            ->get(route('admin.messages.index'))
            ->assertForbidden();
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function invoicePayload(array $overrides = []): array
    {
        return array_merge([
            'amount' => '100000',
            'gst_rate' => '0',
            'billed_at' => now()->toDateString(),
            'due_at' => now()->addDays(30)->toDateString(),
            'notes' => 'Recruitment fee',
        ], $overrides);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function paymentPayload(array $overrides = []): array
    {
        return array_merge([
            'amount' => '10000',
            'paid_at' => now()->toDateString(),
            'method' => PaymentMethod::BankTransfer->value,
            'reference' => 'REF-1',
            'notes' => null,
        ], $overrides);
    }

    /**
     * @return array{0: \App\Models\EmployerProfile, 1: Job, 2: Application, 3: EmployerSubmission, 4: Placement, 5: Employee}
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
        $employee = $placement->employee;
        $this->assertNotNull($employee);

        return [$profile, $job, $application, $submission->fresh(), $placement, $employee];
    }
}
