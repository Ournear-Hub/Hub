<?php

namespace App\Http\Requests\Account;

use App\Models\TalentPoolCompanySubmission;
use App\Support\RecruiterOperationalAccess;
use Illuminate\Foundation\Http\FormRequest;

class StoreCoordinatedTalentPoolMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        $submission = $this->route('submission');

        return $submission instanceof TalentPoolCompanySubmission
            && $this->user() !== null
            && RecruiterOperationalAccess::canViewSubmissionOperational($this->user(), $submission)
            && $this->user()?->can('updateEmployerStatus', $submission);
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
