<?php

namespace App\Http\Requests\Admin;

use App\Enums\InvestorInterestStatus;
use App\Models\InvestorInterest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateInvestorInterestRequest extends FormRequest
{
    public function authorize(): bool
    {
        $interest = $this->route('investorInterest');

        return $interest instanceof InvestorInterest && $this->user()?->can('update', $interest) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(InvestorInterestStatus::class)],
            'indicated_amount' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
