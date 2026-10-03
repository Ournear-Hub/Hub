<?php

namespace App\Http\Requests\Admin;

use App\Enums\InvestorInterestStatus;
use App\Models\InvestorInterest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInvestorInterestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', InvestorInterest::class) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'investor_id' => ['required', 'integer', 'exists:investors,id'],
            'investment_opportunity_id' => ['required', 'integer', 'exists:investment_opportunities,id'],
            'status' => ['nullable', Rule::enum(InvestorInterestStatus::class)],
            'indicated_amount' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
