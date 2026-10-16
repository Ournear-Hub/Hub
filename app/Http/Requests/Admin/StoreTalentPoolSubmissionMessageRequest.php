<?php

namespace App\Http\Requests\Admin;

use App\Models\TalentPoolCompanySubmission;
use App\Support\RecruiterOperationalAccess;
use Illuminate\Foundation\Http\FormRequest;

class StoreTalentPoolSubmissionMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        $submission = $this->route('submission');

        return $submission instanceof TalentPoolCompanySubmission
            && $this->user() !== null
            && RecruiterOperationalAccess::canManageSubmissionOperational($this->user(), $submission);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'max:5000'],
        ];
    }
}
