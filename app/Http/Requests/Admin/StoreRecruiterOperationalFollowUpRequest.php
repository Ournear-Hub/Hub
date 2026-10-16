<?php

namespace App\Http\Requests\Admin;

use App\Enums\RecruiterOperationalFollowUpType;
use App\Enums\RecruitmentOperationalVisibility;
use App\Models\TalentPoolCompanySubmission;
use App\Support\RecruiterOperationalAccess;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRecruiterOperationalFollowUpRequest extends FormRequest
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
            'follow_up_type' => ['required', 'string', Rule::enum(RecruiterOperationalFollowUpType::class)],
            'occurred_at' => ['nullable', 'date'],
            'summary' => ['required', 'string', 'max:5000'],
            'outcome' => ['nullable', 'string', 'max:2000'],
            'issue' => ['nullable', 'string', 'max:2000'],
            'next_action' => ['nullable', 'string', 'max:2000'],
            'next_action_due_at' => ['nullable', 'date'],
            'visibility' => ['required', 'string', Rule::enum(RecruitmentOperationalVisibility::class)],
        ];
    }
}
