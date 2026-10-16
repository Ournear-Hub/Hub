<?php

namespace App\Http\Requests\Account;

use App\Enums\CandidateInterviewResponse;
use App\Models\Application;
use App\Models\ApplicationInterview;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CandidateInterviewResponseRequest extends FormRequest
{
    public function authorize(): bool
    {
        $application = $this->route('application');
        $interview = $this->route('interview');

        return $application instanceof Application
            && $interview instanceof ApplicationInterview
            && (int) $interview->application_id === (int) $application->id
            && $this->user()?->can('respondToInterview', $application) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'response' => ['required', Rule::enum(CandidateInterviewResponse::class)],
            'note' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
