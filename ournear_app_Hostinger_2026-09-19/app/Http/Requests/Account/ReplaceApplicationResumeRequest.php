<?php

namespace App\Http\Requests\Account;

use App\Models\Application;
use Illuminate\Foundation\Http\FormRequest;

class ReplaceApplicationResumeRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $application = $this->route('application');

        return $user?->isCandidate() === true
            && $application instanceof Application
            && (int) $application->user_id === (int) $user->id;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'resume' => [
                'required',
                'file',
                'max:5120',
                'mimes:pdf,doc,docx',
                'mimetypes:application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'resume.mimes' => 'The resume must be a PDF, DOC, or DOCX file.',
            'resume.mimetypes' => 'The resume must be a PDF, DOC, or DOCX file.',
            'resume.max' => 'The resume must not be larger than 5 MB.',
        ];
    }
}
