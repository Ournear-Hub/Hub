<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ProtectsPublicForms;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreApplicationRequest extends FormRequest
{
    use ProtectsPublicForms;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            ...$this->publicProtectionRules(),
            'full_name' => ['required', 'string', 'max:255'],
            'mobile' => ['required', 'string', 'max:40'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('applications', 'email')->where(
                    fn ($query) => $query->where('job_id', $this->route('job')?->id)
                ),
            ],
            'current_location' => ['required', 'string', 'max:255'],
            'experience' => ['required', 'string', 'max:255'],
            'qualification' => ['required', 'string', 'max:255'],
            'resume' => [
                'required_without:candidate_resume_id',
                'nullable',
                'file',
                'max:5120',
                'mimes:pdf,doc,docx',
                'mimetypes:application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ],
            'candidate_resume_id' => ['nullable', 'integer'],
            'additional_information' => ['nullable', 'string', 'max:5000'],
            'consent' => ['accepted'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $merge = [];
        if ($this->has('email')) {
            $merge['email'] = strtolower(trim((string) $this->input('email')));
        }
        if ($this->exists('candidate_resume_id') && $this->input('candidate_resume_id') === '') {
            $merge['candidate_resume_id'] = null;
        }

        if ($merge !== []) {
            $this->merge($merge);
        }
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'consent.accepted' => 'Please confirm that the information provided is accurate and that we may process this application.',
            'email.unique' => 'An application for this role has already been submitted with this email address.',
            'resume.mimes' => 'The resume must be a PDF, DOC, or DOCX file.',
            'resume.mimetypes' => 'The resume must be a PDF, DOC, or DOCX file.',
            'resume.max' => 'The resume must not be larger than 5 MB.',
            'resume.required_without' => 'Please upload a resume or choose one from your library.',
        ];
    }
}
