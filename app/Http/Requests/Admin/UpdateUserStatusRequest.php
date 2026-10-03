<?php

namespace App\Http\Requests\Admin;

use App\Enums\AccountStatus;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        $target = $this->route('user');

        return $target instanceof User && $this->user()?->can('deactivate', $target) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'account_status' => ['nullable', Rule::enum(AccountStatus::class)],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function status(): AccountStatus
    {
        $value = $this->input('account_status');
        if (is_string($value) && AccountStatus::tryFrom($value)) {
            return AccountStatus::from($value);
        }

        return $this->boolean('is_active') ? AccountStatus::Active : AccountStatus::Inactive;
    }
}
