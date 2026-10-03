<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Enums\PlacementStatus;
use App\Enums\RecruitmentActivityType;
use App\Enums\ReplacementReason;
use App\Enums\ReplacementStatus;
use App\Models\Application;
use App\Models\Employee;
use App\Models\EmployerSubmission;
use App\Models\Invoice;
use App\Models\Job;
use App\Models\Payment;
use App\Models\Placement;
use App\Models\RecruitmentActivity;
use App\Models\Replacement;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class FinanceLifecycleTest extends TestCase
{
    public function test_joining_does_not_create_an_invoice_and_only_a_joined_employee_can_be_billed(): void
    {
        $admin = $this->makeAdmin();
        [$profile, $job, $application, $submission, $placement, $employee] = $this->joinedEmployee();

        $this->assertSame(0, Invoice::query()->count());
        $this->assertSame(0, Payment::query()->count());

        $this->actingAs($admin)
            ->get(route('admin.employees.index', ['employee' => $employee->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Employees/Index')
                ->where('selected.can_create_invoice', true)
                ->where('selected.invoice', null));

        [, , , $unjoinedSubmission] = $this->selectedSubmission();
        $this->actingAs($admin)->post(route('admin.submissions.place', $unjoinedSubmission));
        $openPlacement = Placement::query()->where('employer_submission_id', $unjoinedSubmission->id)->first();
        $this->assertSame(PlacementStatus::Open, $openPlacement->status);

        $orphan = Employee::query()->create([
            'placement_id' => $openPlacement->id,
            'employer_profile_id' => $openPlacement->employer_profile_id,
            'job_id' => $openPlacement->job_id,
            'application_id' => $openPlacement->application_id,
            'employer_submission_id' => $openPlacement->employer_submission_id,
            'joined_at' => now(),
            'created_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->from(route('admin.employees.index'))
            ->post(route('admin.employees.invoices.store', $orphan), $this->invoicePayload())
            ->assertSessionHasErrors('employee');

        $this->assertSame(0, Invoice::query()->count());
        $this->assertSame($profile->id, $employee->employer_profile_id);
        $this->assertSame($job->id, $employee->job_id);
        $this->assertSame($application->id, $employee->application_id);
        $this->assertSame($submission->id, $employee->employer_submission_id);
        $this->assertSame($placement->id, $employee->placement_id);
    }

    public function test_invoice_retains_recruitment_lineage_and_historical_amounts(): void
    {
        $admin = $this->makeAdmin();
        [$profile, $job, $application, $submission, $placement, $employee] = $this->joinedEmployee();

        $this->actingAs($admin)
            ->post(route('admin.employees.invoices.store', $employee), $this->invoicePayload([
                'amount' => '100000',
                'gst_rate' => '18',
                'notes' => 'Site Engineer recruitment fee',
            ]))
            ->assertRedirect();

        $invoice = Invoice::query()->where('employee_id', $employee->id)->first();
        $this->assertNotNull($invoice);
        $this->assertStringStartsWith('ONT-I-', (string) $invoice->business_id);
        $this->assertSame(InvoiceStatus::Draft, $invoice->status);
        $this->assertSame('INR', $invoice->currency);
        $this->assertSame('100000.00', (string) $invoice->amount);
        $this->assertSame('18.00', (string) $invoice->gst_rate);
        $this->assertSame('18000.00', (string) $invoice->gst_amount);
        $this->assertSame('118000.00', (string) $invoice->total_amount);
        $this->assertSame($profile->id, $invoice->employer_profile_id);
        $this->assertSame($job->id, $invoice->job_id);
        $this->assertSame($application->id, $invoice->application_id);
        $this->assertSame($submission->id, $invoice->employer_submission_id);
        $this->assertSame($placement->id, $invoice->placement_id);
        $this->assertSame($employee->id, $invoice->employee_id);
        $this->assertNull($invoice->replacement_id);
        $this->assertTrue(RecruitmentActivity::query()
            ->where('invoice_id', $invoice->id)
            ->where('type', RecruitmentActivityType::InvoiceCreated->value)
            ->exists());

        $this->actingAs($admin)
            ->from(route('admin.finance.index', ['invoice' => $invoice->id]))
            ->post(route('admin.invoices.issue', $invoice))
            ->assertRedirect();

        $invoice->refresh();
        $this->assertSame(InvoiceStatus::Issued, $invoice->status);
        $this->assertNotNull($invoice->issued_at);
        $this->assertSame('118000.00', (string) $invoice->total_amount);

        $this->actingAs($admin)
            ->from(route('admin.employees.index'))
            ->post(route('admin.employees.invoices.store', $employee), $this->invoicePayload())
            ->assertSessionHasErrors('employee');

        $this->assertSame(1, Invoice::query()->count());
    }

    public function test_partial_payments_fully_paid_transition_and_overpayment_protection(): void
    {
        $admin = $this->makeAdmin();
        [, , , , , $employee] = $this->joinedEmployee();
        $this->actingAs($admin)->post(route('admin.employees.invoices.store', $employee), $this->invoicePayload([
            'amount' => '100000',
            'gst_rate' => '0',
        ]));
        $invoice = Invoice::query()->where('employee_id', $employee->id)->first();

        $this->actingAs($admin)
            ->from(route('admin.finance.index'))
            ->post(route('admin.invoices.payments.store', $invoice), $this->paymentPayload(['amount' => '40000']))
            ->assertSessionHasErrors('status');

        $this->actingAs($admin)->from(route('admin.finance.index'))->post(route('admin.invoices.issue', $invoice));

        $this->actingAs($admin)
            ->from(route('admin.finance.index', ['invoice' => $invoice->id]))
            ->post(route('admin.invoices.payments.store', $invoice), $this->paymentPayload([
                'amount' => '40000',
                'reference' => 'NEFT-001',
            ]))
            ->assertRedirect();

        $invoice->refresh()->load('payments');
        $this->assertSame(InvoiceStatus::PartiallyPaid, $invoice->status);
        $this->assertSame('40000.00', $invoice->receivedAmount());
        $this->assertSame('60000.00', $invoice->outstandingAmount());
        $this->assertSame('100000.00', (string) $invoice->amount);
        $this->assertSame('100000.00', (string) $invoice->total_amount);
        $payment = $invoice->payments->first();
        $this->assertNotNull($payment);
        $this->assertStringStartsWith('ONT-M-', (string) $payment->business_id);
        $this->assertSame(PaymentMethod::BankTransfer, $payment->method);
        $this->assertSame($invoice->employer_profile_id, $payment->employer_profile_id);

        $this->actingAs($admin)
            ->from(route('admin.finance.index'))
            ->post(route('admin.invoices.payments.store', $invoice), $this->paymentPayload(['amount' => '60000.01']))
            ->assertSessionHasErrors('amount');

        $this->actingAs($admin)
            ->from(route('admin.finance.index'))
            ->post(route('admin.invoices.cancel', $invoice))
            ->assertSessionHasErrors('status');

        $this->actingAs($admin)
            ->from(route('admin.finance.index'))
            ->post(route('admin.invoices.payments.store', $invoice), $this->paymentPayload(['amount' => '60000']))
            ->assertRedirect();

        $invoice->refresh()->load('payments');
        $this->assertSame(InvoiceStatus::Paid, $invoice->status);
        $this->assertSame('100000.00', $invoice->receivedAmount());
        $this->assertSame('0.00', $invoice->outstandingAmount());
        $this->assertSame(2, $invoice->payments->count());
        $this->assertSame('100000.00', (string) $invoice->total_amount);
        $this->assertFalse($invoice->canRecordPayment());
    }

    public function test_draft_and_unpaid_issued_invoices_can_be_cancelled_and_due_dates_are_stored(): void
    {
        $admin = $this->makeAdmin();
        [, , , , , $employee] = $this->joinedEmployee();
        $billedAt = now()->subDays(10)->toDateString();
        $dueAt = now()->subDay()->toDateString();

        $this->actingAs($admin)->post(route('admin.employees.invoices.store', $employee), $this->invoicePayload([
            'billed_at' => $billedAt,
            'due_at' => $dueAt,
        ]));
        $draft = Invoice::query()->where('employee_id', $employee->id)->first();
        $this->assertSame($billedAt, $draft->billed_at?->toDateString());
        $this->assertSame($dueAt, $draft->due_at?->toDateString());
        $this->assertFalse($draft->isOverdue());

        $this->actingAs($admin)
            ->from(route('admin.finance.index'))
            ->post(route('admin.invoices.cancel', $draft), ['notes' => 'Wrong amount'])
            ->assertRedirect();
        $this->assertSame(InvoiceStatus::Cancelled, $draft->fresh()->status);

        $this->actingAs($admin)->post(route('admin.employees.invoices.store', $employee), $this->invoicePayload([
            'amount' => '50000',
            'billed_at' => $billedAt,
            'due_at' => $dueAt,
        ]))->assertRedirect();
        $issued = Invoice::query()->where('employee_id', $employee->id)->where('status', InvoiceStatus::Draft->value)->first();
        $this->actingAs($admin)->from(route('admin.finance.index'))->post(route('admin.invoices.issue', $issued));
        $issued->refresh();
        $this->assertTrue($issued->isOverdue());
        $this->assertSame($dueAt, $issued->due_at?->toDateString());
        $this->assertSame('50000.00', (string) $issued->total_amount);

        $this->actingAs($admin)
            ->get(route('admin.finance.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Finance/Index')
                ->where('summary.billed', '50000.00')
                ->where('summary.received', '0.00')
                ->where('summary.outstanding', '50000.00')
                ->where('summary.overdue', '50000.00'));
    }

    public function test_employer_finance_totals_and_workspace_show_issued_invoices_only(): void
    {
        $admin = $this->makeAdmin();
        [$profile, , , , , $employee] = $this->joinedEmployee();
        $this->actingAs($admin)->post(route('admin.employees.invoices.store', $employee), $this->invoicePayload([
            'amount' => '80000',
        ]));
        $invoice = Invoice::query()->where('employee_id', $employee->id)->first();

        $this->actingAs($admin)
            ->get(route('admin.employers.index', ['employer' => $profile->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('finance.billed', '0.00')
                ->where('finance.outstanding', '0.00')
                ->where('invoices.0.status', InvoiceStatus::Draft->value));

        $this->actingAs($admin)->from(route('admin.finance.index'))->post(route('admin.invoices.issue', $invoice));
        $this->actingAs($admin)
            ->from(route('admin.finance.index'))
            ->post(route('admin.invoices.payments.store', $invoice), $this->paymentPayload(['amount' => '30000']));

        $this->actingAs($admin)
            ->get(route('admin.employers.index', ['employer' => $profile->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('finance.currency', 'INR')
                ->where('finance.billed', '80000.00')
                ->where('finance.received', '30000.00')
                ->where('finance.outstanding', '50000.00')
                ->where('invoices.0.status', InvoiceStatus::PartiallyPaid->value)
                ->where('payments.0.amount', '30000.00'));

        $this->actingAs($admin)
            ->get(route('admin.placements.index', ['placement' => $employee->placement_id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('selected.invoice.business_id', $invoice->fresh()->business_id)
                ->where('selected.invoice.status', InvoiceStatus::PartiallyPaid->value));
    }

    public function test_replacement_is_not_auto_billed_and_original_invoice_remains_historical(): void
    {
        $admin = $this->makeAdmin();
        [$profile, $job, $originalApplication, $originalSubmission, $originalPlacement, $originalEmployee] = $this->joinedEmployee();
        $this->actingAs($admin)->post(route('admin.employees.invoices.store', $originalEmployee), $this->invoicePayload([
            'amount' => '90000',
            'notes' => 'Original recruitment fee',
        ]));
        $originalInvoice = Invoice::query()->where('employee_id', $originalEmployee->id)->first();
        $this->actingAs($admin)->from(route('admin.finance.index'))->post(route('admin.invoices.issue', $originalInvoice));
        $originalSnapshot = $originalInvoice->fresh()->only(['amount', 'gst_amount', 'total_amount', 'currency', 'employee_id', 'placement_id']);

        $this->actingAs($admin)->post(route('admin.employees.replacements.store', $originalEmployee), [
            'reason' => ReplacementReason::Resigned->value,
        ]);
        $replacement = Replacement::query()->where('original_employee_id', $originalEmployee->id)->first();
        $candidateApplication = $this->shortlistedApplicationFor($job, 'Bala Replacement');
        $this->actingAs($admin)->post(route('admin.replacements.identify', $replacement), [
            'application_id' => $candidateApplication->id,
        ]);
        $this->actingAs($admin)->post(route('admin.applications.submit', $candidateApplication));
        $candidateSubmission = EmployerSubmission::query()->where('application_id', $candidateApplication->id)->first();
        $this->actingAs($admin)->post(route('admin.submissions.select', $candidateSubmission));
        $this->actingAs($admin)->post(route('admin.submissions.place', $candidateSubmission->fresh()));
        $replacementPlacement = Placement::query()->where('application_id', $candidateApplication->id)->first();
        $this->actingAs($admin)->post(route('admin.placements.offer', $replacementPlacement), [
            'offered_at' => now()->toDateString(),
        ]);
        $this->actingAs($admin)->post(route('admin.placements.accept', $replacementPlacement));
        $this->actingAs($admin)->post(route('admin.placements.confirm-joining', $replacementPlacement), [
            'expected_joining_date' => now()->toDateString(),
        ]);
        $this->actingAs($admin)->post(route('admin.placements.join', $replacementPlacement), [
            'actual_joining_date' => now()->toDateString(),
        ]);

        $replacementEmployee = Employee::query()->where('application_id', $candidateApplication->id)->first();
        $this->assertNotNull($replacementEmployee);
        $this->assertSame(ReplacementStatus::Completed, $replacement->fresh()->status);
        $this->assertSame(1, Invoice::query()->count());
        $this->assertSame($originalSnapshot['total_amount'], $originalInvoice->fresh()->total_amount);
        $this->assertSame($originalEmployee->id, $originalInvoice->fresh()->employee_id);
        $this->assertSame($originalPlacement->id, $originalInvoice->fresh()->placement_id);
        $this->assertNull($originalInvoice->fresh()->replacement_id);
        $this->assertSame(PlacementStatus::Joined, $originalPlacement->fresh()->status);
        $this->assertSame($originalApplication->id, $originalEmployee->fresh()->application_id);
        $this->assertSame($originalSubmission->id, $originalEmployee->fresh()->employer_submission_id);

        $this->actingAs($admin)
            ->post(route('admin.employees.invoices.store', $replacementEmployee), $this->invoicePayload([
                'amount' => '25000',
                'notes' => 'Optional replacement billing',
            ]))
            ->assertRedirect();

        $replacementInvoice = Invoice::query()->where('employee_id', $replacementEmployee->id)->first();
        $this->assertNotNull($replacementInvoice);
        $this->assertSame($replacement->id, $replacementInvoice->replacement_id);
        $this->assertSame($profile->id, $replacementInvoice->employer_profile_id);
        $this->assertSame($job->id, $replacementInvoice->job_id);
        $this->assertSame($candidateApplication->id, $replacementInvoice->application_id);
        $this->assertSame($candidateSubmission->id, $replacementInvoice->employer_submission_id);
        $this->assertSame($replacementPlacement->id, $replacementInvoice->placement_id);
        $this->assertSame('25000.00', (string) $replacementInvoice->total_amount);
        $this->assertSame(2, Invoice::query()->count());
        $this->assertSame('90000.00', (string) $originalInvoice->fresh()->total_amount);
    }

    public function test_candidate_and_employer_cannot_manage_finance(): void
    {
        $admin = $this->makeAdmin();
        [, , , , , $employee] = $this->joinedEmployee();
        $this->actingAs($admin)->post(route('admin.employees.invoices.store', $employee), $this->invoicePayload());
        $invoice = Invoice::query()->where('employee_id', $employee->id)->first();

        $this->actingAs($this->makeCandidate())
            ->get(route('admin.finance.index'))
            ->assertForbidden();
        $this->actingAs($this->makeCandidate())
            ->post(route('admin.invoices.issue', $invoice))
            ->assertForbidden();
        $this->actingAs($this->makeEmployer())
            ->get(route('admin.finance.index'))
            ->assertForbidden();
        $this->actingAs($this->makeEmployer())
            ->post(route('admin.invoices.payments.store', $invoice), $this->paymentPayload())
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
        [$profile, $job, $application, $submission] = $this->selectedSubmission();
        $this->actingAs($admin)->post(route('admin.submissions.place', $submission));
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

        return [$profile, $job, $application, $submission, $placement, $employee];
    }

    /**
     * @return array{0: \App\Models\EmployerProfile, 1: Job, 2: Application, 3: EmployerSubmission}
     */
    private function selectedSubmission(): array
    {
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

        $this->actingAs($this->makeAdmin())->post(route('admin.applications.submit', $application));
        $submission = EmployerSubmission::query()->where('application_id', $application->id)->first();
        $this->assertNotNull($submission);
        $this->actingAs($this->makeAdmin())->post(route('admin.submissions.select', $submission));

        return [$profile, $job, $application, $submission->fresh()];
    }

    private function shortlistedApplicationFor(Job $job, string $name): Application
    {
        $candidate = $this->makeCandidate(['name' => $name]);

        return Application::factory()->create([
            'job_id' => $job->id,
            'user_id' => $candidate->id,
            'full_name' => $name,
            'status' => ApplicationStatus::Shortlisted,
        ]);
    }
}
