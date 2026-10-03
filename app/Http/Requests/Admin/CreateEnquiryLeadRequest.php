<?php

namespace App\Http\Requests\Admin;

use App\Models\CrmLead;
use App\Models\EmployerEnquiry;
use Illuminate\Foundation\Http\FormRequest;

class CreateEnquiryLeadRequest extends FormRequest
{
    public function authorize(): bool
    {
        $enquiry = $this->route('employerEnquiry');

        return $enquiry instanceof EmployerEnquiry
            && $this->user()?->can('update', $enquiry) === true
            && $this->user()?->can('create', CrmLead::class) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'confirm_duplicates' => ['sometimes', 'boolean'],
        ];
    }
}
