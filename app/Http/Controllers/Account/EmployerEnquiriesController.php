<?php

namespace App\Http\Controllers\Account;

use App\Enums\EmployerEnquiryStatus;
use App\Http\Controllers\Controller;
use App\Models\EmployerEnquiry;
use App\Support\EmployerStaffingServices;
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

        return Inertia::render('Account/Enquiries', [
            'company' => [
                'name' => $profile?->displayCompany() ?? $user->name,
                'business_id' => $profile?->business_id,
            ],
            'summary' => EmployerStaffingServices::workspaceSummary($user),
            'recent_requests' => $recentRequests,
            'enquiries' => $enquiries,
            'filters' => [
                'q' => $search,
                'status' => $status ?? '',
            ],
            'status_options' => EmployerStaffingServices::statusFilterOptions(),
            'request_href' => EmployerStaffingServices::REQUEST_HREF,
            'support_href' => EmployerStaffingServices::SUPPORT_CONTACT_HREF,
        ]);
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

        return Inertia::render('Account/Enquiries/Show', [
            'company' => [
                'name' => $profile?->displayCompany() ?? $user->name,
                'business_id' => $profile?->business_id,
            ],
            'enquiry' => $employerEnquiry->toAccountArray(),
            'list_filters' => $listFilters,
            'request_href' => EmployerStaffingServices::REQUEST_HREF,
            'support_href' => EmployerStaffingServices::SUPPORT_CONTACT_HREF,
        ]);
    }
}
