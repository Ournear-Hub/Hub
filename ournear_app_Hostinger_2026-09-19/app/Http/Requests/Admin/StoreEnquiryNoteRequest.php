<?php

namespace App\Http\Requests\Admin;

use App\Models\Application;
use App\Models\ContactEnquiry;
use App\Models\EmployerEnquiry;
use Illuminate\Foundation\Http\FormRequest;

class StoreEnquiryNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        $application = $this->route('application');
        if ($application instanceof Application) {
            return $this->user()?->can('update', $application) === true;
        }

        $enquiry = $this->route('employerEnquiry');
        if ($enquiry instanceof EmployerEnquiry) {
            return $this->user()?->can('update', $enquiry) === true;
        }

        $contact = $this->route('contactEnquiry');
        if ($contact instanceof ContactEnquiry) {
            return $this->user()?->canManageWebsite() === true;
        }

        return false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'note' => ['required', 'string', 'max:5000'],
        ];
    }
}
