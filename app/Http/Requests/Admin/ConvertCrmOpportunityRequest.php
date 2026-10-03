<?php

namespace App\Http\Requests\Admin;

use App\Models\CrmOpportunity;
use Illuminate\Foundation\Http\FormRequest;

class ConvertCrmOpportunityRequest extends FormRequest
{
    public function authorize(): bool
    {
        $opportunity = $this->route('opportunity');

        return $opportunity instanceof CrmOpportunity
            && $this->user()?->can('convert', $opportunity) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'employer_profile_id' => ['nullable', 'integer', 'exists:employer_profiles,id'],
            'create_employer' => ['sometimes', 'boolean'],
            'email' => ['nullable', 'email', 'max:255'],
            'confirm_duplicates' => ['sometimes', 'boolean'],
        ];
    }
}
