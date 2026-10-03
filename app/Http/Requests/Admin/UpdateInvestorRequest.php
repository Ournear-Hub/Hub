<?php

namespace App\Http\Requests\Admin;

use App\Models\Investor;
use Illuminate\Foundation\Http\FormRequest;

class UpdateInvestorRequest extends FormRequest
{
    public function authorize(): bool
    {
        $investor = $this->route('investor');

        return $investor instanceof Investor && $this->user()?->can('update', $investor) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'display_name' => ['required', 'string', 'max:255'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'mobile' => ['nullable', 'string', 'max:40'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
