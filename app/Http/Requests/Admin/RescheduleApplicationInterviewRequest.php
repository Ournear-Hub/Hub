<?php

namespace App\Http\Requests\Admin;

use App\Enums\InterviewMode;
use App\Enums\InterviewType;
use App\Models\ApplicationInterview;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RescheduleApplicationInterviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        $interview = $this->route('interview');

        return $interview instanceof ApplicationInterview
            && $this->user()?->can('update', $interview) === true;
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
            'interviewer_name' => ['nullable', 'string', 'max:255'],
            'interviewer_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'type' => ['nullable', Rule::enum(InterviewType::class)],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
