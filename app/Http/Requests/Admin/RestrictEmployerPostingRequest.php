<?php

namespace App\Http\Requests\Admin;

use App\Models\EmployerProfile;
use Illuminate\Foundation\Http\FormRequest;

class RestrictEmployerPostingRequest extends FormRequest
{
    public function authorize(): bool
    {
        $profile = $this->route('employer');

        return $profile instanceof EmployerProfile && $this->user()?->can('update', $profile) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'restricted' => ['required', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->exists('restricted')) {
            $this->merge([
                'restricted' => $this->boolean('restricted'),
            ]);
        }
    }
}
