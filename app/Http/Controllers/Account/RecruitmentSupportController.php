<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Http\Requests\Account\StoreAccountRecruitmentRequirementRequest;
use App\Services\EnquirySubmissionService;
use App\Services\SubmissionMailService;
use App\Support\EmployerWorkspaceDestination;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RecruitmentSupportController extends Controller
{
    public function create(Request $request): Response|RedirectResponse
    {
        $user = $request->user();
        abort_unless($user !== null, 403);

        if (! $user->canAccessHiringWorkspace()) {
            EmployerWorkspaceDestination::remember($request, '/account/recruitment-support');

            return redirect()
                ->route('account.hiring', ['destination' => '/account/recruitment-support'])
                ->with('warning', 'Activate your employer workspace before submitting a recruitment requirement.');
        }

        $user->loadMissing('employerProfile');
        $profile = $user->employerProfile;

        return Inertia::render('Account/RecruitmentSupport/RequirementForm', [
            'defaults' => [
                'company_name' => $profile?->displayCompany() ?? $user->name,
                'contact_person' => $profile?->contact_person ?: $user->name,
                'mobile_number' => $profile?->mobile_number ?: ($user->mobile ?? ''),
                'email' => $user->email,
                'industry' => $profile?->industry ?? '',
            ],
        ]);
    }

    public function store(
        StoreAccountRecruitmentRequirementRequest $request,
        EnquirySubmissionService $enquiries,
        SubmissionMailService $mail,
    ): RedirectResponse {
        $user = $request->user();
        abort_unless($user !== null, 403);

        $user->loadMissing('employerProfile');
        $profile = $user->employerProfile;
        $data = $request->validated();

        $message = collect([
            'Recruitment requirement submission',
            $profile?->company_name ? 'Company: '.$profile->company_name : null,
            'Designation: '.$data['designation'],
            'Industry: '.$data['industry'],
            filled($data['job_category'] ?? null) ? 'Job category: '.$data['job_category'] : null,
            'Candidates needed: '.$data['number_of_candidates'],
            filled($data['qualifications'] ?? null) ? 'Qualifications: '.$data['qualifications'] : null,
            filled($data['skills'] ?? null) ? 'Skills: '.$data['skills'] : null,
            filled($data['experience_range'] ?? null) ? 'Experience: '.$data['experience_range'] : null,
            'Work location: '.$data['work_location'],
            filled($data['employment_type'] ?? null) ? 'Employment type: '.$data['employment_type'] : null,
            filled($data['hiring_timeline'] ?? null) ? 'Hiring timeline: '.$data['hiring_timeline'] : null,
            filled($data['additional_requirements'] ?? null) ? 'Additional requirements: '.$data['additional_requirements'] : null,
            'Account ID: '.$user->business_id,
        ])->filter()->implode("\n");

        $enquiry = $enquiries->storeContactEnquiry([
            'full_name' => $profile?->contact_person ?: $user->name,
            'mobile_number' => $profile?->mobile_number ?: ($user->mobile ?? ''),
            'email' => $user->email,
            'interested_in' => 'Recruitment',
            'message' => $message,
        ]);

        $outcome = $mail->notifyContactEnquiry($enquiry);

        return redirect()
            ->route('account.recruitment-support.create')
            ->with('success', $outcome->publicMessage(
                'Your recruitment requirement has been submitted.',
                'A confirmation email has been sent when delivery is enabled.',
            ));
    }
}
