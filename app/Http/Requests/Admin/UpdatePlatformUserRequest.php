<?php

namespace App\Http\Requests\Admin;

use App\Enums\PreferredLanguage;
use App\Http\Requests\Concerns\ValidatesAccountMobile;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdatePlatformUserRequest extends FormRequest
{
    use ValidatesAccountMobile;

    public function authorize(): bool
    {
        $target = $this->route('user');

        return $target instanceof User && $this->user()?->can('update', $target) === true;
    }

    protected function prepareForValidation(): void
    {
        $this->mergeNormalizedMobile();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $target = $this->route('user');
        $userId = $target instanceof User ? $target->id : null;

        return [
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:64', 'alpha_dash', Rule::unique('users', 'username')->ignore($userId)],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'mobile' => ['nullable', 'string', 'max:32'],
            'location' => ['nullable', 'string', 'max:255'],
            'preferred_language' => ['required', Rule::enum(PreferredLanguage::class)],
            'photo' => ['nullable', 'image', 'max:5120'],
            'remove_photo' => ['sometimes', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $target = $this->route('user');
        $this->validateNormalizedMobile(
            $validator,
            $target instanceof User ? $target->mobile : null,
            $target instanceof User ? $target->id : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function identity(): array
    {
        return [
            'name' => $this->string('name')->toString(),
            'username' => $this->string('username')->toString(),
            'email' => $this->string('email')->toString(),
            'mobile' => $this->string('mobile')->toString() ?: null,
            'location' => $this->string('location')->toString() ?: null,
            'preferred_language' => $this->string('preferred_language')->toString(),
        ];
    }
}
