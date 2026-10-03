<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class HiringCommandCenterRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user instanceof User && $user->canViewHiringCommandCenter();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'line' => ['nullable', 'string', Rule::in(['all', 'ats', 'partnership', 'staffing', 'jobs'])],
            'job_id' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function line(): string
    {
        $line = $this->string('line')->toString();

        return in_array($line, ['ats', 'partnership', 'staffing', 'jobs'], true) ? $line : 'all';
    }

    public function jobId(): ?int
    {
        $id = $this->integer('job_id');

        return $id > 0 ? $id : null;
    }
}
