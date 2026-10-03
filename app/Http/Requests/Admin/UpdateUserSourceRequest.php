<?php

namespace App\Http\Requests\Admin;

use App\Enums\RegistrationSource;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserSourceRequest extends FormRequest
{
    public function authorize(): bool
    {
        $target = $this->route('user');

        return $target instanceof User && $this->user()?->can('create', User::class) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'registration_source' => ['required', Rule::enum(RegistrationSource::class)],
        ];
    }

    public function source(): RegistrationSource
    {
        return RegistrationSource::from($this->string('registration_source')->toString());
    }
}
