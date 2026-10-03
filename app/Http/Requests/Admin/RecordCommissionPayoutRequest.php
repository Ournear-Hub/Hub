<?php

namespace App\Http\Requests\Admin;

use App\Enums\PaymentMethod;
use App\Models\CommissionPayment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RecordCommissionPayoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', CommissionPayment::class) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'channel_partner_id' => ['required', 'integer', 'exists:channel_partners,id'],
            'entry_ids' => ['required', 'array', 'min:1'],
            'entry_ids.*' => ['integer', 'exists:commission_entries,id'],
            'paid_at' => ['required', 'date'],
            'method' => ['required', Rule::enum(PaymentMethod::class)],
            'reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
