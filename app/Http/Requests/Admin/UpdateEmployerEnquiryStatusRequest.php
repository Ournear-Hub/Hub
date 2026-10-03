<?php

namespace App\Http\Requests\Admin;

use App\Enums\EmployerEnquiryStatus;
use App\Models\EmployerEnquiry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEmployerEnquiryStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        $enquiry = $this->route('employerEnquiry');

        return $enquiry instanceof EmployerEnquiry
            && $this->user()?->can('update', $enquiry) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(EmployerEnquiryStatus::class)],
        ];
    }
}
