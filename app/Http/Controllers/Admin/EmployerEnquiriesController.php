<?php

namespace App\Http\Controllers\Admin;

use App\Enums\EmployerEnquiryStatus;
use App\Enums\PermissionName;
use App\Enums\RecruitmentActivityType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CreateEnquiryLeadRequest;
use App\Http\Requests\Admin\StoreEnquiryNoteRequest;
use App\Http\Requests\Admin\UpdateEmployerEnquiryStatusRequest;
use App\Models\EmployerEnquiry;
use App\Services\CrmLeadService;
use App\Services\RecruitmentActivityService;
use App\Support\AppendsEnquiryNotes;
use App\Support\EmployerEnquiryQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EmployerEnquiriesController extends Controller
{
    use AppendsEnquiryNotes;

    public function index(Request $request, EmployerEnquiryQuery $enquiries): Response
    {
        $this->authorize('viewAny', EmployerEnquiry::class);

        $page = $enquiries->adminIndex($request)->through(function (EmployerEnquiry $enquiry) {
            $enquiry->loadMissing('crmLead');

            return $enquiry->toAdminArray();
        });

        $selected = null;
        $selectedId = $request->integer('enquiry');
        if ($selectedId > 0) {
            $selected = EmployerEnquiry::query()->with('crmLead')->find($selectedId);
        }

        return Inertia::render('Admin/EmployerEnquiries/Index', [
            'enquiries' => $page,
            'selected' => $selected?->toAdminArray(),
            'summary' => $enquiries->summary(),
            'filters' => [
                'search' => $request->string('search')->toString(),
                'status' => $request->string('status')->toString(),
                'industry' => $request->string('industry')->toString(),
                'project_location' => $request->string('project_location')->toString(),
                'workforce_type' => $request->string('workforce_type')->toString(),
                'date_from' => $request->string('date_from')->toString(),
                'date_to' => $request->string('date_to')->toString(),
                'enquiry' => $selectedId > 0 ? (string) $selectedId : '',
            ],
            'options' => $enquiries->filterOptions(),
            'statuses' => array_map(fn (EmployerEnquiryStatus $status) => $status->value, EmployerEnquiryStatus::cases()),
            'can_create_lead' => $request->user()?->hasPermission(PermissionName::EmployersManage->value) === true,
        ]);
    }

    public function updateStatus(
        UpdateEmployerEnquiryStatusRequest $request,
        EmployerEnquiry $employerEnquiry,
        RecruitmentActivityService $activities,
    ): RedirectResponse {
        $from = $employerEnquiry->status?->value;
        $to = $request->enum('status', EmployerEnquiryStatus::class);
        $employerEnquiry->update([
            'status' => $to,
        ]);

        if ($from !== $to->value) {
            $activities->record(
                actor: $request->user(),
                type: RecruitmentActivityType::EnquiryStatusChanged,
                enquiry: $employerEnquiry,
                body: 'Employer enquiry status changed from '.$from.' to '.$to->value.'.',
                fromStatus: $from,
                toStatus: $to->value,
            );
        }

        return back()->with('success', 'Enquiry status updated.');
    }

    public function createLead(
        CreateEnquiryLeadRequest $request,
        EmployerEnquiry $employerEnquiry,
        CrmLeadService $leads,
    ): RedirectResponse {
        $lead = $leads->createFromEnquiry($employerEnquiry, $request->user(), $request->validated());

        return redirect()
            ->route('admin.crm.index', ['lead' => $lead->id])
            ->with('success', 'CRM lead created from enquiry.');
    }

    public function storeNote(StoreEnquiryNoteRequest $request, EmployerEnquiry $employerEnquiry): RedirectResponse
    {
        $employerEnquiry->internal_notes = $this->appendNote(
            $employerEnquiry->notes(),
            $request->string('note')->toString(),
            $request->user()
        );
        $employerEnquiry->save();

        return back()->with('success', 'Internal note added.');
    }
}
