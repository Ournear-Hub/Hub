<?php

namespace App\Support;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Models\EmployerProfile;
use App\Models\Invoice;
use App\Models\Job;
use App\Models\Payment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class FinanceQuery
{
    /**
     * @return LengthAwarePaginator<int, Invoice>
     */
    public function adminIndex(Request $request): LengthAwarePaginator
    {
        $query = Invoice::query()
            ->with([
                'employer.user',
                'job',
                'employee.application',
                'payments',
            ])
            ->latest('billed_at');

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

        $status = trim((string) $request->query('status', ''));
        if ($status !== '') {
            $query->where('status', $status);
        }

        if ($request->boolean('overdue')) {
            $query->whereIn('status', [InvoiceStatus::Issued->value, InvoiceStatus::PartiallyPaid->value])
                ->whereDate('due_at', '<', now()->toDateString());
        }

        $from = trim((string) $request->query('date_from', ''));
        if ($from !== '') {
            $query->whereDate('billed_at', '>=', $from);
        }

        $to = trim((string) $request->query('date_to', ''));
        if ($to !== '') {
            $query->whereDate('billed_at', '<=', $to);
        }

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
                'application',
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

        return [
            'search' => $request->string('search')->toString(),
            'employer_id' => $request->string('employer_id')->toString(),
            'job_id' => $request->string('job_id')->toString(),
            'status' => $request->string('status')->toString(),
            'overdue' => $request->boolean('overdue') ? '1' : '',
            'date_from' => $request->string('date_from')->toString(),
            'date_to' => $request->string('date_to')->toString(),
            'invoice' => $selectedId > 0 ? (string) $selectedId : '',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function options(): array
    {
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
            'statuses' => array_map(fn (InvoiceStatus $status) => $status->value, InvoiceStatus::cases()),
            'methods' => array_map(fn (PaymentMethod $method) => $method->value, PaymentMethod::cases()),
        ];
    }
}
