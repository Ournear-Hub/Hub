<?php

namespace App\Http\Requests\Admin;

use App\Enums\InvestmentOpportunityStatus;
use App\Models\InvestmentOpportunity;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TransitionInvestmentOpportunityRequest extends FormRequest
{
    public function authorize(): bool
    {
        $opportunity = $this->route('investmentOpportunity');

        return $opportunity instanceof InvestmentOpportunity && $this->user()?->can('update', $opportunity) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(InvestmentOpportunityStatus::class)],
        ];
    }
}
