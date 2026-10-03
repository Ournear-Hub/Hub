<?php

namespace App\Http\Controllers\Admin;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CancelInvoiceRequest;
use App\Http\Requests\Admin\CreateInvoiceRequest;
use App\Http\Requests\Admin\IssueInvoiceRequest;
use App\Http\Requests\Admin\RecordPaymentRequest;
use App\Models\Employee;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\FinanceService;
use App\Support\FinanceQuery;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FinanceController extends Controller
{
    public function index(Request $request, FinanceQuery $finance): Response
    {
        $this->authorize('viewAny', Invoice::class);

        $page = $finance->adminIndex($request)
            ->through(fn (Invoice $invoice) => $invoice->toAdminListArray());

        $payments = $finance->paymentsIndex($request)
            ->through(fn (Payment $payment) => $payment->toAdminArray());

        $selected = $finance->selected($request);
        if ($selected && $request->user()?->cannot('view', $selected)) {
            $selected = null;
        }

        $selectedPayment = $finance->selectedPayment($request);
        if ($selectedPayment && $selectedPayment->invoice && $request->user()?->cannot('view', $selectedPayment->invoice)) {
            $selectedPayment = null;
        }

        if ($selectedPayment !== null) {
            $selected = null;
        }

        return Inertia::render('Admin/Finance/Index', [
            'invoices' => $page,
            'payments' => $payments,
            'selected' => $selected?->toAdminArray(),
            'selectedPayment' => $selectedPayment?->toAdminDetailArray(),
            'summary' => $finance->summary(),
            'dashboard' => $finance->dashboard(),
            'recentPayments' => $finance->recentPayments($request),
            'filters' => $finance->filters($request),
            'options' => $finance->options(),
            'statuses' => array_map(fn (InvoiceStatus $status) => $status->value, InvoiceStatus::cases()),
            'methods' => array_map(fn (PaymentMethod $method) => $method->value, PaymentMethod::cases()),
            'can_manage' => $request->user()?->can('create', Invoice::class) === true,
        ]);
    }

    public function store(
        CreateInvoiceRequest $request,
        Employee $employee,
        FinanceService $service,
    ): RedirectResponse {
        $invoice = $service->createInvoice(
            $employee,
            $request->user(),
            Money::of($request->input('amount')),
            Money::of($request->input('gst_rate', 0)),
            $request->string('billed_at')->toString(),
            $request->string('due_at')->toString(),
            $request->input('notes'),
        );

        return redirect()
            ->route('admin.finance.index', ['invoice' => $invoice->id])
            ->with('success', 'Draft invoice created. Issue it to freeze the billed amount.');
    }

    public function issue(
        IssueInvoiceRequest $request,
        Invoice $invoice,
        FinanceService $service,
    ): RedirectResponse {
        $service->issue($invoice, $request->user());

        return back()->with('success', 'Invoice issued. Historical amounts cannot be changed.');
    }

    public function cancel(
        CancelInvoiceRequest $request,
        Invoice $invoice,
        FinanceService $service,
    ): RedirectResponse {
        $service->cancel($invoice, $request->user(), $request->input('notes'));

        return back()->with('success', 'Invoice cancelled.');
    }

    public function recordPayment(
        RecordPaymentRequest $request,
        Invoice $invoice,
        FinanceService $service,
    ): RedirectResponse {
        $service->recordPayment(
            $invoice,
            $request->user(),
            Money::of($request->input('amount')),
            $request->string('paid_at')->toString(),
            PaymentMethod::from($request->string('method')->toString()),
            $request->input('reference'),
            $request->input('notes'),
        );

        return back()->with('success', 'Payment recorded.');
    }
}
