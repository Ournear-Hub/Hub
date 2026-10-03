<?php

namespace App\Http\Requests\Admin;

use App\Models\Investor;
use Illuminate\Foundation\Http\FormRequest;

class StoreInvestorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Investor::class) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'display_name' => ['required', 'string', 'max:255'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'name' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'mobile' => ['nullable', 'string', 'max:40'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
