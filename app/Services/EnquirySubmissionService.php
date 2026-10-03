<?php

namespace App\Services;

use App\Enums\ContactEnquiryStatus;
use App\Enums\EmployerEnquiryStatus;
use App\Enums\UserActivityType;
use App\Models\ContactEnquiry;
use App\Models\EmployerEnquiry;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class EnquirySubmissionService
{
    public function __construct(private UserActivityService $activities) {}

    /**
     * @param  array<string, mixed>  $validated
     */
    public function storeEmployerEnquiry(array $validated): EmployerEnquiry
    {
        $email = Str::lower($validated['email']);
        $company = trim($validated['company_name']);

        $duplicate = EmployerEnquiry::query()
            ->where('email', $email)
            ->where('company_name', $company)
            ->where('created_at', '>=', now()->subMinutes(15))
            ->exists();

        if ($duplicate) {
            throw ValidationException::withMessages([
                'email' => 'An enquiry from this company and email was submitted recently. Please wait before sending another.',
            ]);
        }

        $enquiry = EmployerEnquiry::query()->create([
            'user_id' => $this->authenticatedEmployerId(),
            'company_name' => $company,
            'contact_person' => $validated['contact_person'],
            'mobile_number' => $validated['mobile_number'],
            'email' => $email,
            'industry' => $validated['industry'],
            'project_location' => $validated['project_location'],
            'workforce_type' => $validated['workforce_type'],
            'number_of_people' => $validated['number_of_people'],
            'required_skills' => $validated['required_skills'],
            'expected_start_date' => $validated['expected_start_date'] ?? null,
            'training_required' => filter_var($validated['training_required'], FILTER_VALIDATE_BOOLEAN),
            'additional_requirements' => $validated['additional_requirements'] ?? null,
            'status' => EmployerEnquiryStatus::New,
            'internal_notes' => [],
        ]);

        $employer = $enquiry->user_id ? User::query()->find($enquiry->user_id) : null;
        if ($employer instanceof User) {
            $this->activities->record(
                $employer,
                UserActivityType::EmployerEnquirySubmitted,
                'Submitted an employer enquiry.',
                $employer,
                ['enquiry_id' => $enquiry->id, 'company_name' => $enquiry->company_name],
            );
        }

        return $enquiry;
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    public function storeContactEnquiry(array $validated): ContactEnquiry
    {
        $email = filled($validated['email'] ?? null) ? Str::lower((string) $validated['email']) : null;
        $mobile = trim($validated['mobile_number']);

        $duplicate = ContactEnquiry::query()
            ->where('created_at', '>=', now()->subMinutes(15))
            ->where(function ($query) use ($mobile, $email) {
                $query->where('mobile_number', $mobile);
                if ($email) {
                    $query->orWhere('email', $email);
                }
            })
            ->exists();

        if ($duplicate) {
            throw ValidationException::withMessages([
                'mobile_number' => 'An enquiry was submitted recently with these details. Please wait before sending another.',
            ]);
        }

        return ContactEnquiry::query()->create([
            'full_name' => $validated['full_name'],
            'mobile_number' => $mobile,
            'email' => $email,
            'interested_in' => $validated['interested_in'],
            'message' => $validated['message'],
            'status' => ContactEnquiryStatus::New,
            'internal_notes' => [],
        ]);
    }

    private function authenticatedEmployerId(): ?int
    {
        $user = Auth::user();

        return $user instanceof User && $user->isEmployer() ? $user->id : null;
    }
}
