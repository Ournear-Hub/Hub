<?php

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Enums\PlacementStatus;
use App\Enums\RecruitmentActivityType;
use App\Models\Employee;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FinanceService
{
    public function __construct(private RecruitmentActivityService $activities) {}

    public function createInvoice(
        Employee $employee,
        User $actor,
        string $amount,
        string $gstRate,
        string $billedAt,
        string $dueAt,
        ?string $notes = null,
    ): Invoice {
        return DB::transaction(function () use ($employee, $actor, $amount, $gstRate, $billedAt, $dueAt, $notes) {
            $employee = Employee::query()->lockForUpdate()->with(['placement', 'employer', 'job', 'application', 'submission', 'resultingReplacement'])->findOrFail($employee->id);
            $this->assertCanBill($employee);

            $fee = Money::of($amount);
            $rate = Money::of($gstRate);
            if (Money::cmp($fee, '0') <= 0) {
                throw ValidationException::withMessages([
                    'amount' => 'The recruitment fee must be greater than zero.',
                ]);
            }
            if (Money::cmp($rate, '0') < 0 || Money::cmp($rate, '100') > 0) {
                throw ValidationException::withMessages([
                    'gst_rate' => 'GST rate must be between 0 and 100.',
                ]);
            }

            $gstAmount = Money::gstAmount($fee, $rate);
            $total = Money::add($fee, $gstAmount);

            $invoice = Invoice::query()->create([
                'employer_profile_id' => $employee->employer_profile_id,
                'job_id' => $employee->job_id,
                'application_id' => $employee->application_id,
                'employer_submission_id' => $employee->employer_submission_id,
                'placement_id' => $employee->placement_id,
                'employee_id' => $employee->id,
                'replacement_id' => $employee->resultingReplacement?->id,
                'status' => InvoiceStatus::Draft,
                'currency' => Invoice::CURRENCY,
                'amount' => $fee,
                'gst_rate' => $rate,
                'gst_amount' => $gstAmount,
                'total_amount' => $total,
                'billed_at' => $billedAt,
                'due_at' => $dueAt,
                'notes' => $notes,
                'created_by' => $actor->id,
            ]);

            $this->record(
                $actor,
                RecruitmentActivityType::InvoiceCreated,
                $invoice,
                'Draft invoice created for recruitment fee '.$invoice->currency.' '.$total.'.',
                null,
                InvoiceStatus::Draft->value,
            );

            return $invoice->fresh() ?? $invoice;
        });
    }

    public function issue(Invoice $invoice, User $actor): Invoice
    {
        return DB::transaction(function () use ($invoice, $actor) {
            $invoice = Invoice::query()->lockForUpdate()->findOrFail($invoice->id);
            if (! $invoice->canIssue()) {
                throw ValidationException::withMessages([
                    'status' => 'Only a draft invoice can be issued.',
                ]);
            }

            $from = $invoice->status;
            $invoice->status = InvoiceStatus::Issued;
            $invoice->issued_at = now();
            $invoice->save();

            $this->record(
                $actor,
                RecruitmentActivityType::InvoiceIssued,
                $invoice,
                'Invoice issued. Historical amounts are frozen.',
                $from->value,
                InvoiceStatus::Issued->value,
            );

            return $invoice->fresh() ?? $invoice;
        });
    }

    public function cancel(Invoice $invoice, User $actor, ?string $notes = null): Invoice
    {
        return DB::transaction(function () use ($invoice, $actor, $notes) {
            $invoice = Invoice::query()->lockForUpdate()->with('payments')->findOrFail($invoice->id);
            if (! $invoice->canCancel()) {
                throw ValidationException::withMessages([
                    'status' => 'This invoice cannot be cancelled.',
                ]);
            }

            $from = $invoice->status;
            $invoice->status = InvoiceStatus::Cancelled;
            $invoice->cancelled_at = now();
            $invoice->notes = $notes ?: $invoice->notes;
            $invoice->save();

            $this->record(
                $actor,
                RecruitmentActivityType::InvoiceCancelled,
                $invoice,
                'Invoice cancelled.'.($notes ? ' '.$notes : ''),
                $from->value,
                InvoiceStatus::Cancelled->value,
            );

            return $invoice->fresh() ?? $invoice;
        });
    }

    public function recordPayment(
        Invoice $invoice,
        User $actor,
        string $amount,
        string $paidAt,
        PaymentMethod $method,
        ?string $reference = null,
        ?string $notes = null,
    ): Payment {
        return DB::transaction(function () use ($invoice, $actor, $amount, $paidAt, $method, $reference, $notes) {
            $invoice = Invoice::query()->lockForUpdate()->with('payments')->findOrFail($invoice->id);
            if (! $invoice->canRecordPayment()) {
                throw ValidationException::withMessages([
                    'status' => 'A payment can only be recorded against an issued invoice with an outstanding balance.',
                ]);
            }

            $received = Money::of($amount);
            if (Money::cmp($received, '0') <= 0) {
                throw ValidationException::withMessages([
                    'amount' => 'Payment amount must be greater than zero.',
                ]);
            }

            $outstanding = $invoice->outstandingAmount();
            if (Money::cmp($received, $outstanding) === 1) {
                throw ValidationException::withMessages([
                    'amount' => 'Payment cannot exceed the outstanding balance of '.$invoice->currency.' '.$outstanding.'.',
                ]);
            }

            $payment = Payment::query()->create([
                'invoice_id' => $invoice->id,
                'employer_profile_id' => $invoice->employer_profile_id,
                'currency' => $invoice->currency,
                'amount' => $received,
                'paid_at' => $paidAt,
                'method' => $method,
                'reference' => $reference,
                'notes' => $notes,
                'recorded_by' => $actor->id,
            ]);

            $invoice->refresh()->load('payments');
            $from = $invoice->status;
            $invoice->status = Money::cmp($invoice->outstandingAmount(), '0') === 0
                ? InvoiceStatus::Paid
                : InvoiceStatus::PartiallyPaid;
            $invoice->save();

            $this->activities->record(
                actor: $actor,
                type: RecruitmentActivityType::PaymentRecorded,
                application: $invoice->application,
                body: 'Payment '.$payment->business_id.' recorded: '.$payment->currency.' '.$received.'.',
                fromStatus: $from->value,
                toStatus: $invoice->status->value,
                submission: $invoice->submission,
                employer: $invoice->employer,
                placement: $invoice->placement,
                employee: $invoice->employee,
                replacement: $invoice->replacement,
                invoice: $invoice,
                payment: $payment,
            );

            return $payment->fresh() ?? $payment;
        });
    }

    private function assertCanBill(Employee $employee): void
    {
        $placement = $employee->placement;
        if ($placement === null || $placement->status !== PlacementStatus::Joined) {
            throw ValidationException::withMessages([
                'employee' => 'An invoice can only be created after the candidate has joined.',
            ]);
        }

        if ($employee->employer === null || $employee->job === null || $employee->application === null || $employee->submission === null) {
            throw ValidationException::withMessages([
                'employee' => 'The employee must retain a complete employer, job, application, and submission lineage.',
            ]);
        }

        $exists = Invoice::query()
            ->where('employee_id', $employee->id)
            ->where('status', '!=', InvoiceStatus::Cancelled->value)
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'employee' => 'An active invoice already exists for this employee.',
            ]);
        }
    }

    private function record(
        User $actor,
        RecruitmentActivityType $type,
        Invoice $invoice,
        string $body,
        ?string $fromStatus,
        ?string $toStatus,
        ?Payment $payment = null,
    ): void {
        $invoice->loadMissing(['application', 'submission', 'employer', 'placement', 'employee', 'replacement']);

        $this->activities->record(
            actor: $actor,
            type: $type,
            application: $invoice->application,
            body: $body,
            fromStatus: $fromStatus,
            toStatus: $toStatus,
            submission: $invoice->submission,
            employer: $invoice->employer,
            placement: $invoice->placement,
            employee: $invoice->employee,
            replacement: $invoice->replacement,
            invoice: $invoice,
            payment: $payment,
        );
    }
}
