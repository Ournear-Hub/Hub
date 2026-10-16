<?php

namespace App\Http\Controllers\Account;

use App\Enums\EmployerEnquiryStatus;
use App\Enums\EmployerStaffingAccessStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Account\StoreAccountStaffingEnquiryRequest;
use App\Models\EmployerEnquiry;
use App\Models\StaffingCandidateSubmission;
use App\Services\EnquirySubmissionService;
use App\Services\SubmissionMailService;
use App\Support\EmployerStaffingServices;
use App\Support\PublicContent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EmployerEnquiriesController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        abort_unless($user !== null, 403);

        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', 'string', 'in:'.implode(',', array_column(EmployerEnquiryStatus::cases(), 'value'))],
        ]);

        $search = trim((string) ($validated['q'] ?? ''));
        $status = $validated['status'] ?? null;

        $query = EmployerStaffingServices::ownedEnquiriesQuery($user);

        if ($search !== '') {
            $like = '%'.$search.'%';
            $query->where(function ($builder) use ($like) {
                $builder->where('company_name', 'like', $like)
                    ->orWhere('workforce_type', 'like', $like)
                    ->orWhere('project_location', 'like', $like)
                    ->orWhere('required_skills', 'like', $like)
                    ->orWhere('additional_requirements', 'like', $like);
            });
        }

        if (is_string($status) && $status !== '') {
            $query->where('status', $status);
        }

        $enquiries = $query
            ->paginate(10)
            ->withQueryString()
            ->through(fn (EmployerEnquiry $enquiry) => $enquiry->toAccountArray());

        $recentRequests = EmployerStaffingServices::ownedEnquiriesQuery($user)
            ->limit(5)
            ->get()
            ->map(fn (EmployerEnquiry $enquiry) => $enquiry->toAccountArray())
            ->values()
            ->all();

        $profile = $user->employerProfile;
        $user->loadMissing('employerProfile');

        $staffingStatus = $profile?->staffing_access_status ?? EmployerStaffingAccessStatus::NotRequested;

        return Inertia::render('Account/Enquiries', [
            'company' => [
                'name' => $profile?->displayCompany() ?? $user->name,
                'business_id' => $profile?->business_id,
            ],
            'staffing_access' => [
                'status' => $staffingStatus->value,
                'label' => $staffingStatus->label(),
                'can_submit_requirement' => $staffingStatus->canSubmitStaffingRequirement(),
                'can_request_access' => in_array($staffingStatus, [
                    EmployerStaffingAccessStatus::NotRequested,
                    EmployerStaffingAccessStatus::Rejected,
                    EmployerStaffingAccessStatus::Revoked,
                ], true),
            ],
            'summary' => EmployerStaffingServices::workspaceSummary($user),
            'recent_requests' => $recentRequests,
            'enquiries' => $enquiries,
            'filters' => [
                'q' => $search,
                'status' => $status ?? '',
            ],
            'status_options' => EmployerStaffingServices::statusFilterOptions(),
            'staffing_form' => [
                'industries' => PublicContent::industries(),
                'workforce_types' => ['Permanent', 'Contract', 'Temporary', 'Project teams'],
                'defaults' => [
                    'company_name' => $profile?->displayCompany() ?? $user->name,
                    'contact_person' => $profile?->contact_person ?: $user->name,
                    'mobile_number' => $profile?->mobile_number ?: ($user->mobile ?? ''),
                    'email' => $user->email,
                    'industry' => $profile?->industry ?? '',
                ],
            ],
        ]);
    }

    public function store(
        StoreAccountStaffingEnquiryRequest $request,
        EnquirySubmissionService $enquiries,
        SubmissionMailService $mail,
    ): RedirectResponse {
        $profile = $request->user()?->employerProfile;
        $status = $profile?->staffing_access_status ?? EmployerStaffingAccessStatus::NotRequested;
        if (! $status->canSubmitStaffingRequirement()) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'staffing_access' => 'Staffing module access must be approved before submitting requirements.',
            ]);
        }

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

    public function show(Request $request, EmployerEnquiry $employerEnquiry): Response
    {
        $user = $request->user();
        abort_unless($user !== null, 403);
        abort_unless((int) $employerEnquiry->user_id === (int) $user->id, 404);

        $listFilters = [
            'q' => trim((string) $request->query('q', '')),
            'status' => trim((string) $request->query('status', '')),
        ];

        $profile = $user->employerProfile;

        $candidateSubmissions = StaffingCandidateSubmission::query()
            ->where('employer_enquiry_id', $employerEnquiry->id)
            ->with(['candidateUser.candidateProfile'])
            ->latest('submitted_at')
            ->get()
            ->map(fn (StaffingCandidateSubmission $row) => $row->toClientArray())
            ->values()
            ->all();

        return Inertia::render('Account/Enquiries/Show', [
            'company' => [
                'name' => $profile?->displayCompany() ?? $user->name,
                'business_id' => $profile?->business_id,
            ],
            'enquiry' => $employerEnquiry->toAccountArray(),
            'candidate_submissions' => $candidateSubmissions,
            'list_filters' => $listFilters,
            'support_href' => EmployerStaffingServices::SUPPORT_CONTACT_HREF,
        ]);
    }
}
