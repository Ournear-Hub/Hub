<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Http\Requests\Account\StoreAccountStaffingEnquiryRequest;
use App\Services\EnquirySubmissionService;
use App\Services\SubmissionMailService;
use App\Support\PublicContent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StaffingRequestController extends Controller
{
    public function create(Request $request): Response|RedirectResponse
    {
        $user = $request->user();
        abort_unless($user !== null, 403);

        if (! $user->canAccessHiringWorkspace()) {
            return redirect()
                ->route('account.hiring')
                ->with('warning', 'Activate your employer workspace before submitting a staffing request.');
        }

        $user->loadMissing('employerProfile');
        $profile = $user->employerProfile;

        return Inertia::render('Account/Staffing/RequirementForm', [
            'industries' => PublicContent::industries(),
            'workforce_types' => ['Permanent', 'Contract', 'Temporary', 'Project teams'],
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
        StoreAccountStaffingEnquiryRequest $request,
        EnquirySubmissionService $enquiries,
        SubmissionMailService $mail,
    ): RedirectResponse {
        $validated = $request->validated();
        $validated['from_staffing'] = '1';
        $validated['expected_start_date'] = $validated['period_from'];
        $validated['training_required'] = filter_var($validated['training_required'], FILTER_VALIDATE_BOOLEAN) ? '1' : '0';
        unset($validated['from_staffing']);

        $enquiry = $enquiries->storeEmployerEnquiry($validated);
        $outcome = $mail->notifyEmployerEnquiry($enquiry);

        return redirect()
            ->route('account.enquiries.index')
            ->with('success', $outcome->publicMessage(
                'Your staffing requirement has been submitted.',
                'OurNear will follow up on your request.',
            ));
    }
}
