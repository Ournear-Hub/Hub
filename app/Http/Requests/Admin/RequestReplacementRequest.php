<?php

namespace App\Http\Requests\Admin;

use App\Enums\ReplacementReason;
use App\Models\Employee;
use App\Models\Replacement;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RequestReplacementRequest extends FormRequest
{
    public function authorize(): bool
    {
        $employee = $this->route('employee');

        return $employee instanceof Employee
            && $this->user()?->can('view', $employee) === true
            && $this->user()?->can('create', Replacement::class) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'reason' => ['required', Rule::enum(ReplacementReason::class)],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
