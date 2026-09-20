<?php

namespace App\Http\Controllers\Admin;

use App\Enums\EmployerEnquiryStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreEnquiryNoteRequest;
use App\Http\Requests\Admin\UpdateEmployerEnquiryStatusRequest;
use App\Models\EmployerEnquiry;
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
        $page = $enquiries->adminIndex($request)->through(fn (EmployerEnquiry $enquiry) => $enquiry->toAdminArray());

        $selected = null;
        $selectedId = $request->integer('enquiry');
        if ($selectedId > 0) {
            $selected = EmployerEnquiry::query()->find($selectedId);
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
        ]);
    }

    public function updateStatus(UpdateEmployerEnquiryStatusRequest $request, EmployerEnquiry $employerEnquiry): RedirectResponse
    {
        $employerEnquiry->update([
            'status' => $request->enum('status', EmployerEnquiryStatus::class),
        ]);

        return back()->with('success', 'Enquiry status updated.');
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
