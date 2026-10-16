<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Http\Requests\Account\StoreAccountContactEnquiryRequest;
use App\Services\EnquirySubmissionService;
use App\Services\SubmissionMailService;
use App\Services\WebsiteContentService;
use App\Support\PublicContent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ContactController extends Controller
{
    public function show(Request $request, WebsiteContentService $cms): Response
    {
        $user = $request->user();
        abort_unless($user !== null && $user->isAccountMember(), 403);

        $user->loadMissing('employerProfile');
        $profile = $user->employerProfile;
        $showEmployerEnquiry = $user->canAccessHiringWorkspace();

        return Inertia::render('Account/Contact', [
            'content' => PublicContent::common(),
            'cms' => $cms->publicPage('contact'),
            'showEmployerEnquiry' => $showEmployerEnquiry,
            'interestOptions' => $showEmployerEnquiry
                ? ['Recruitment', 'Training', 'Workforce solutions', 'General enquiry']
                : [],
            'defaults' => $showEmployerEnquiry ? [
                'full_name' => $profile?->contact_person ?: $user->name,
                'mobile_number' => $profile?->mobile_number ?: ($user->mobile ?? ''),
                'email' => $user->email,
            ] : [],
        ]);
    }

    public function store(
        StoreAccountContactEnquiryRequest $request,
        EnquirySubmissionService $enquiries,
        SubmissionMailService $mail,
    ): RedirectResponse {
        $user = $request->user();
        abort_unless($user !== null && $user->canAccessHiringWorkspace(), 403);

        $validated = $request->validated();
        $user->loadMissing('employerProfile');
        $profile = $user->employerProfile;
        $contextLines = array_values(array_filter([
            filled($profile?->company_name) ? 'Employer company: '.$profile->company_name : null,
            'Account ID: '.$user->business_id,
            'Signed-in email: '.$user->email,
        ]));
        if ($contextLines !== []) {
            $validated['message'] = implode("\n", $contextLines)."\n\n".$validated['message'];
        }

        $enquiry = $enquiries->storeContactEnquiry($validated);
        $outcome = $mail->notifyContactEnquiry($enquiry);

        return redirect()
            ->route('account.contact')
            ->with('success', $outcome->publicMessage(
                'Thank you. Your enquiry has been submitted.',
                'A confirmation email has been sent to you.',
            ));
    }
}
