<?php

namespace App\Http\Requests\Admin;

use App\Models\Employee;
use App\Models\Invoice;
use Illuminate\Foundation\Http\FormRequest;

class CreateInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        $employee = $this->route('employee');

        return $employee instanceof Employee
            && $this->user()?->can('view', $employee) === true
            && $this->user()?->can('create', Invoice::class) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'gt:0'],
            'gst_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'billed_at' => ['required', 'date'],
            'due_at' => ['required', 'date', 'after_or_equal:billed_at'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
