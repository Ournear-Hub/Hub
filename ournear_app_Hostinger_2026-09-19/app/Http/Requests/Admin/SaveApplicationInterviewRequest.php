<?php

namespace App\Http\Requests\Admin;

use App\Enums\InterviewMode;
use App\Enums\InterviewOutcome;
use App\Models\Application;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveApplicationInterviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        $application = $this->route('application');

        return $application instanceof Application
            && $this->user()?->can('update', $application) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'scheduled_at' => ['required', 'date'],
            'mode' => ['required', Rule::enum(InterviewMode::class)],
            'location_or_link' => ['nullable', 'string', 'max:500'],
            'interviewer_name' => ['required', 'string', 'max:255'],
            'outcome' => ['required', Rule::enum(InterviewOutcome::class)],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
