<?php

namespace App\Http\Requests\Admin;

use App\Models\InvestmentOpportunity;
use Illuminate\Foundation\Http\FormRequest;

class UpdateInvestmentOpportunityRequest extends FormRequest
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
            'title' => ['required', 'string', 'max:255'],
            'summary' => ['nullable', 'string', 'max:5000'],
            'sector' => ['nullable', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'indicated_size' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'opens_at' => ['nullable', 'date'],
            'closes_at' => ['nullable', 'date', 'after_or_equal:opens_at'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
