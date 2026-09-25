<?php

namespace App\Http\Requests\Account;

use App\Support\CandidateFileRules;
use Illuminate\Foundation\Http\FormRequest;

class ReplaceCandidateResumeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isCandidate() === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'file' => CandidateFileRules::resume(),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return CandidateFileRules::resumeMessages();
    }
}
