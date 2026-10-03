<?php

namespace App\Http\Requests\Admin;

use App\Models\ApplicationInterview;
use Illuminate\Foundation\Http\FormRequest;

class StoreInterviewScorecardRequest extends FormRequest
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
            'scorecard_template_id' => ['nullable', 'integer', 'exists:scorecard_templates,id'],
            'qualitative' => ['nullable', 'array'],
            'numeric_score' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
