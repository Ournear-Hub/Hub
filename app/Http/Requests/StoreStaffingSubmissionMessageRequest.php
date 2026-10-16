<?php

namespace App\Http\Requests;

use App\Models\StaffingCandidateSubmission;
use App\Support\RecruiterOperationalAccess;
use Illuminate\Foundation\Http\FormRequest;

class StoreStaffingSubmissionMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        $submission = $this->route('staffingSubmission');

        return $submission instanceof StaffingCandidateSubmission
            && $this->user() !== null
            && RecruiterOperationalAccess::canMessageOnStaffingSubmission($this->user(), $submission);
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
