<?php

namespace App\Support;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Models\CandidateProfile;
use App\Models\EmployerProfile;
use App\Models\Invoice;
use App\Models\Job;
use App\Models\Payment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class FinanceQuery
{
    public const PAYMENT_STATUS_RECORDED = 'Recorded';

    public const PAYMENT_STATUS_FAILED = 'Failed';

    public const PAYMENT_STATUS_REFUNDED = 'Refunded';

    public const PAYMENT_STATUS_CANCELLED = 'Cancelled';

    /**
     * @return LengthAwarePaginator<int, Invoice>
     */
    public function adminIndex(Request $request): LengthAwarePaginator
    {
        $query = Invoice::query()
            ->with([
                'employer.user',
                'job',
                'application.user.candidateProfile',
                'placement',
                'employee.application',
                'payments',
            ])
            ->latest('billed_at');

        $this->applyInvoiceFilters($query, $request);

        return $query->paginate(15)->withQueryString();
    }

    /**
     * @return LengthAwarePaginator<int, Payment>
     */
    public function paymentsIndex(Request $request): LengthAwarePaginator
    {
        $query = Payment::query()
            ->with([
                'invoice.application.user.candidateProfile',
                'invoice.placement',
                'invoice.job',
                'invoice.employee.application',
                'employer.user',
                'recorder:id,name',
            ])
            ->latest('paid_at');

        $this->applyPaymentFilters($query, $request);

        return $query->paginate(15)->withQueryString();
    }

    public function selected(Request $request): ?Invoice
    {
        $id = $request->integer('invoice');
        if ($id <= 0) {
            return null;
        }

        return Invoice::query()
            ->with([
                'employer.user',
                'job',
                'application.user.candidateProfile',
                'submission',
                'placement',
                'employee.application',
                'replacement',
                'payments.recorder:id,name',
                'creator:id,name',
                'activities.actor:id,name',
            ])
            ->find($id);
    }

    public function selectedPayment(Request $request): ?Payment
    {
        $id = $request->integer('payment');
        if ($id <= 0) {
            return null;
        }

        return Payment::query()
            ->with([
                'invoice.application.user.candidateProfile',
                'invoice.submission',
                'invoice.placement',
                'invoice.job',
                'invoice.employer.user',
                'invoice.employee.application',
                'invoice.replacement',
                'employer.user',
                'recorder:id,name',
                'activities.actor:id,name',
            ])
            ->find($id);
    }

    /**
     * @return array<string, mixed>
     */
    public function dashboard(): array
    {
        $statusCounts = Invoice::query()
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $pending = (int) $statusCounts->get(InvoiceStatus::Issued->value, 0)
            + (int) $statusCounts->get(InvoiceStatus::PartiallyPaid->value, 0);
        $paid = (int) $statusCounts->get(InvoiceStatus::Paid->value, 0);
        $cancelled = (int) $statusCounts->get(InvoiceStatus::Cancelled->value, 0);
        $draft = (int) $statusCounts->get(InvoiceStatus::Draft->value, 0);
        $amounts = $this->summary();

        return [
            'currency' => Invoice::CURRENCY,
            'total_invoices' => Invoice::query()->count(),
            'pending' => $pending,
            'paid' => $paid,
            'failed' => 0,
            'failed_available' => false,
            'refunded' => 0,
            'refunded_available' => false,
            'cancelled' => $cancelled,
            'draft' => $draft,
            'total_invoiced_amount' => $amounts['billed'],
            'total_paid_amount' => $amounts['received'],
            'total_pending_amount' => $amounts['outstanding'],
            'recorded_payments' => Payment::query()->count(),
            'gateway_configured' => false,
            'gateway_message' => Payment::GATEWAY_MESSAGE,
        ];
    }

    /**
     * @return array<string, string>
     */
    public function summary(?int $employerId = null): array
    {
        $query = Invoice::query()
            ->with('payments')
            ->whereIn('status', [
                InvoiceStatus::Issued->value,
                InvoiceStatus::PartiallyPaid->value,
                InvoiceStatus::Paid->value,
            ]);

        if ($employerId) {
            $query->where('employer_profile_id', $employerId);
        }

        $invoices = $query->get();
        $billed = '0.00';
        $received = '0.00';
        $overdue = '0.00';

        foreach ($invoices as $invoice) {
            $billed = Money::add($billed, (string) $invoice->total_amount);
            $received = Money::add($received, $invoice->receivedAmount());
            if ($invoice->isOverdue()) {
                $overdue = Money::add($overdue, $invoice->outstandingAmount());
            }
        }

        return [
            'currency' => Invoice::CURRENCY,
            'billed' => $billed,
            'received' => $received,
            'outstanding' => Money::cmp(Money::sub($billed, $received), '0') < 0 ? '0.00' : Money::sub($billed, $received),
            'overdue' => $overdue,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function recentPayments(Request $request): array
    {
        $query = Payment::query()
            ->with(['invoice', 'employer.user', 'recorder:id,name'])
            ->latest('paid_at');

        $employerId = $request->integer('employer_id');
        if ($employerId > 0) {
            $query->where('employer_profile_id', $employerId);
        }

        return $query->limit(20)->get()->map(fn (Payment $payment) => $payment->toAdminArray())->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function filters(Request $request): array
    {
        $selectedId = $request->integer('invoice');
        $paymentId = $request->integer('payment');
        $viewParam = $request->string('view')->toString();
        if ($viewParam === '') {
            $viewParam = $request->string('tab')->toString();
        }
        $view = $viewParam === 'payments' || $paymentId > 0 ? 'payments' : 'invoices';

        return [
            'search' => $request->string('search')->toString(),
            'employer_id' => $request->string('employer_id')->toString(),
            'job_id' => $request->string('job_id')->toString(),
            'candidate_id' => $request->string('candidate_id')->toString(),
            'status' => $request->string('status')->toString(),
            'invoice_type' => $request->string('invoice_type')->toString(),
            'payment_status' => $request->string('payment_status')->toString(),
            'overdue' => $request->boolean('overdue') ? '1' : '',
            'awaiting_payment' => $request->boolean('awaiting_payment') ? '1' : '',
            'date_from' => $request->string('date_from')->toString(),
            'date_to' => $request->string('date_to')->toString(),
            'view' => $view,
            'invoice' => $selectedId > 0 ? (string) $selectedId : '',
            'payment' => $paymentId > 0 ? (string) $paymentId : '',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function options(): array
    {
        $invoiceApplicationIds = Invoice::query()->select('application_id');

        return [
            'employers' => EmployerProfile::query()
                ->with('user')
                ->orderBy('company_name')
                ->get()
                ->map(fn (EmployerProfile $employer) => $employer->toSelectArray())
                ->all(),
            'jobs' => Job::query()->orderBy('title')->get(['id', 'title', 'business_id'])->map(fn (Job $job) => [
                'id' => $job->id,
                'title' => $job->title,
                'business_id' => $job->business_id,
            ])->all(),
            'candidates' => CandidateProfile::query()
                ->with('user')
                ->whereHas('applications', fn (Builder $application) => $application->whereIn('id', $invoiceApplicationIds))
                ->orderBy('id')
                ->get()
                ->map(fn (CandidateProfile $profile) => [
                    'id' => $profile->id,
                    'label' => trim(implode(' · ', array_filter([
                        $profile->business_id,
                        $profile->user?->name,
                    ]))),
                ])
                ->all(),
            'statuses' => array_map(fn (InvoiceStatus $status) => $status->value, InvoiceStatus::cases()),
            'invoice_types' => ['Recruitment fee', 'Replacement billing'],
            'methods' => array_map(fn (PaymentMethod $method) => $method->value, PaymentMethod::cases()),
            'payment_statuses' => [
                ['value' => self::PAYMENT_STATUS_RECORDED, 'available' => true],
                ['value' => self::PAYMENT_STATUS_FAILED, 'available' => false],
                ['value' => self::PAYMENT_STATUS_REFUNDED, 'available' => false],
                ['value' => self::PAYMENT_STATUS_CANCELLED, 'available' => false],
            ],
        ];
    }

    /**
     * @param  Builder<Invoice>  $query
     */
    private function applyInvoiceFilters(Builder $query, Request $request): void
    {
        $search = trim((string) $request->query('search', ''));
        if ($search !== '') {
            $query->where(function (Builder $inner) use ($search) {
                $inner->where('business_id', 'like', '%'.$search.'%')
                    ->orWhereHas('employer', function (Builder $employer) use ($search) {
                        $employer->where('business_id', 'like', '%'.$search.'%')
                            ->orWhere('company_name', 'like', '%'.$search.'%');
                    })
                    ->orWhereHas('job', function (Builder $job) use ($search) {
                        $job->where('business_id', 'like', '%'.$search.'%')
                            ->orWhere('title', 'like', '%'.$search.'%');
                    })
                    ->orWhereHas('application', fn (Builder $application) => $application->where('full_name', 'like', '%'.$search.'%')
                        ->orWhere('business_id', 'like', '%'.$search.'%'))
                    ->orWhereHas('employee', fn (Builder $employee) => $employee->where('business_id', 'like', '%'.$search.'%'))
                    ->orWhereHas('employee.application', fn (Builder $application) => $application->where('full_name', 'like', '%'.$search.'%'))
                    ->orWhereHas('payments', fn (Builder $payment) => $payment->where('business_id', 'like', '%'.$search.'%')->orWhere('reference', 'like', '%'.$search.'%'));
            });
        }

        $employerId = $request->integer('employer_id');
        if ($employerId > 0) {
            $query->where('employer_profile_id', $employerId);
        }

        $jobId = $request->integer('job_id');
        if ($jobId > 0) {
            $query->where('job_id', $jobId);
        }

        $candidateId = $request->integer('candidate_id');
        if ($candidateId > 0) {
            $query->whereHas('application.user.candidateProfile', fn (Builder $profile) => $profile->whereKey($candidateId));
        }

        $status = trim((string) $request->query('status', ''));
        if ($status !== '') {
            $query->where('status', $status);
        }

        $invoiceType = trim((string) $request->query('invoice_type', ''));
        if ($invoiceType === 'Replacement billing') {
            $query->whereNotNull('replacement_id');
        } elseif ($invoiceType === 'Recruitment fee') {
            $query->whereNull('replacement_id');
        }

        if ($request->boolean('overdue')) {
            $query->whereIn('status', [InvoiceStatus::Issued->value, InvoiceStatus::PartiallyPaid->value])
                ->whereDate('due_at', '<', now()->toDateString());
        }

        if ($request->boolean('awaiting_payment')) {
            $query->whereIn('status', [InvoiceStatus::Issued->value, InvoiceStatus::PartiallyPaid->value]);
        }

        $from = trim((string) $request->query('date_from', ''));
        if ($from !== '') {
            $query->whereDate('billed_at', '>=', $from);
        }

        $to = trim((string) $request->query('date_to', ''));
        if ($to !== '') {
            $query->whereDate('billed_at', '<=', $to);
        }
    }

    /**
     * @param  Builder<Payment>  $query
     */
    private function applyPaymentFilters(Builder $query, Request $request): void
    {
        $status = trim((string) $request->query('payment_status', ''));
        if ($status !== '' && $status !== self::PAYMENT_STATUS_RECORDED) {
            $query->whereRaw('1 = 0');

            return;
        }

        $search = trim((string) $request->query('search', ''));
        if ($search !== '') {
            $query->where(function (Builder $inner) use ($search) {
                $inner->where('business_id', 'like', '%'.$search.'%')
                    ->orWhere('reference', 'like', '%'.$search.'%')
                    ->orWhereHas('invoice', fn (Builder $invoice) => $invoice->where('business_id', 'like', '%'.$search.'%'))
                    ->orWhereHas('invoice.application', fn (Builder $application) => $application->where('full_name', 'like', '%'.$search.'%')
                        ->orWhere('business_id', 'like', '%'.$search.'%'))
                    ->orWhereHas('employer', function (Builder $employer) use ($search) {
                        $employer->where('business_id', 'like', '%'.$search.'%')
                            ->orWhere('company_name', 'like', '%'.$search.'%');
                    });
            });
        }

        $employerId = $request->integer('employer_id');
        if ($employerId > 0) {
            $query->where('employer_profile_id', $employerId);
        }

        $jobId = $request->integer('job_id');
        if ($jobId > 0) {
            $query->whereHas('invoice', fn (Builder $invoice) => $invoice->where('job_id', $jobId));
        }

        $candidateId = $request->integer('candidate_id');
        if ($candidateId > 0) {
            $query->whereHas('invoice.application.user.candidateProfile', fn (Builder $profile) => $profile->whereKey($candidateId));
        }

        $from = trim((string) $request->query('date_from', ''));
        if ($from !== '') {
            $query->whereDate('paid_at', '>=', $from);
        }

        $to = trim((string) $request->query('date_to', ''));
        if ($to !== '') {
            $query->whereDate('paid_at', '<=', $to);
        }
    }
}
