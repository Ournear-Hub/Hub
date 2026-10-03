<?php

namespace App\Http\Requests\Admin;

use App\Models\ContactEnquiry;
use App\Models\CrmLead;
use Illuminate\Foundation\Http\FormRequest;

class CreateContactEnquiryLeadRequest extends FormRequest
{
    public function authorize(): bool
    {
        $enquiry = $this->route('contactEnquiry');

        return $enquiry instanceof ContactEnquiry
            && $this->user()?->can('create', CrmLead::class) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'company_name' => ['nullable', 'string', 'max:255'],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'confirm_duplicates' => ['sometimes', 'boolean'],
        ];
    }
}
