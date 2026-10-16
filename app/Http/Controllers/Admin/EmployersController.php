<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AccountStatus;
use App\Enums\EmployerDocumentType;
use App\Enums\EmployerSubmissionStatus;
use App\Enums\FileLifecycleStatus;
use App\Enums\PermissionName;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReplaceEmployerDocumentRequest;
use App\Http\Requests\Admin\RestrictEmployerPostingRequest;
use App\Http\Requests\Admin\ReviewEmployerDocumentRequest;
use App\Http\Requests\Admin\StoreEmployerDocumentRequest;
use App\Http\Requests\Admin\SyncEmployerTagsRequest;
use App\Http\Requests\Admin\UpdateEmployerCompanyRequest;
use App\Http\Requests\Admin\UpdateEmployerDocumentRequest;
use App\Models\Application;
use App\Models\Employee;
use App\Models\EmployerDocument;
use App\Models\EmployerProfile;
use App\Models\EmployerSubmission;
use App\Models\Industry;
use App\Models\Invoice;
use App\Models\Location;
use App\Models\Payment;
use App\Models\Placement;
use App\Models\Replacement;
use App\Models\Tag;
use App\Http\Requests\Admin\DecideEmployerStaffingAccessRequest;
use App\Http\Requests\Admin\StoreCompanyRelationshipRecruiterAssignmentRequest;
use App\Models\CompanyRelationshipRecruiterAssignment;
use App\Services\CompanyRelationshipRecruiterAssignmentService;
use App\Support\OurNearInternalRecruiters;
use App\Services\EmployerCompanyService;
use App\Services\EmployerStaffingAccessService;
use App\Services\EmployerDocumentLibraryService;
use App\Services\EmployerPrivateFileService;
use App\Services\MasterData\MasterDataService;
use App\Services\TagService;
use App\Support\EmployerQuery;
use App\Support\EmployerTimeline;
use App\Support\FinanceQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EmployersController extends Controller
{
    public function postingApproval(Request $request, EmployerProfile $employer, EmployerCompanyService $companies): RedirectResponse
    {
        $this->authorize('update', $employer);
        $approved = $employer->job_posting_approved_at === null;
        $companies->togglePostingApproval($employer, $request->user());

        return back()->with('success', $approved
            ? 'Employer job posting approved.'
            : 'Employer job posting approval revoked.');
    }

    public function update(
        UpdateEmployerCompanyRequest $request,
        EmployerProfile $employer,
        EmployerCompanyService $companies,
    ): RedirectResponse {
        $companies->updateCompany($employer, $request->companyAttributes(), $request->user());

        return back()->with('success', 'Employer company profile updated.');
    }

    public function restrictPosting(
        RestrictEmployerPostingRequest $request,
        EmployerProfile $employer,
        EmployerCompanyService $companies,
    ): RedirectResponse {
        $restricted = $request->boolean('restricted');
        $companies->setPostingRestricted($employer, $restricted, $request->user());

        return back()->with('success', $restricted
            ? 'Employer job posting restricted.'
            : 'Employer job posting restriction removed.');
    }

    public function syncTags(
        SyncEmployerTagsRequest $request,
        EmployerProfile $employer,
        TagService $tags,
    ): RedirectResponse {
        $user = $employer->user;
        abort_unless($user !== null, 404);

        $tags->syncUserTags($request->user(), $user, $request->tagIds());

        return back()->with('success', 'Employer tags updated.');
    }

    public function storeDocument(
        StoreEmployerDocumentRequest $request,
        EmployerProfile $employer,
        EmployerDocumentLibraryService $library,
    ): RedirectResponse {
        $library->upload(
            $employer,
            $request->file('file'),
            $request->validated(),
            $request->user(),
        );

        return back()->with('success', 'Company document uploaded.');
    }

    public function showDocument(
        Request $request,
        EmployerProfile $employer,
        EmployerDocument $document,
        EmployerPrivateFileService $files,
    ): StreamedResponse {
        $document = $this->documentFor($employer, $document);
        $this->authorize('download', $document);

        return $files->download(
            $document->storage_path,
            $document->original_filename ?: 'document.'.$document->extension,
            $request->string('disposition')->toString() === 'inline',
            $document->storage_disk,
        );
    }

    public function updateDocument(
        UpdateEmployerDocumentRequest $request,
        EmployerProfile $employer,
        EmployerDocument $document,
        EmployerDocumentLibraryService $library,
    ): RedirectResponse {
        $document = $this->documentFor($employer, $document);
        $this->authorize('update', $document);
        $library->update($document, $request->validated(), $request->user());

        return back()->with('success', 'Company document updated.');
    }

    public function replaceDocument(
        ReplaceEmployerDocumentRequest $request,
        EmployerProfile $employer,
        EmployerDocument $document,
        EmployerDocumentLibraryService $library,
    ): RedirectResponse {
        $document = $this->documentFor($employer, $document);
        $this->authorize('update', $document);
        $library->replace($document, $request->file('file'), $request->user());

        return back()->with('success', 'Company document replaced.');
    }

    public function reviewDocument(
        ReviewEmployerDocumentRequest $request,
        EmployerProfile $employer,
        EmployerDocument $document,
        EmployerDocumentLibraryService $library,
    ): RedirectResponse {
        $document = $this->documentFor($employer, $document);
        $this->authorize('verify', $document);
        $library->review(
            $document,
            (string) $request->validated('action'),
            $request->user(),
            $request->validated('review_notes'),
        );

        $verified = $request->validated('action') === 'verify';

        return back()->with('success', $verified
            ? 'Company document verified.'
            : 'Company document rejected.');
    }

    public function destroyDocument(
        Request $request,
        EmployerProfile $employer,
        EmployerDocument $document,
        EmployerDocumentLibraryService $library,
    ): RedirectResponse {
        $document = $this->documentFor($employer, $document);
        $this->authorize('delete', $document);
        $library->delete($document, $request->user());

        return back()->with('success', 'Company document deleted.');
    }

    public function index(
        Request $request,
        EmployerQuery $employers,
        FinanceQuery $finance,
        EmployerTimeline $timeline,
        MasterDataService $masterData,
    ): Response {
        $this->authorize('viewAny', EmployerProfile::class);

        $page = $employers->adminIndex($request)
            ->through(fn (EmployerProfile $employer) => $employer->toAdminArray());

        $selected = $employers->selected($request);
        if ($selected && $request->user()?->cannot('view', $selected)) {
            $selected = null;
        }

        $actor = $request->user();

        return Inertia::render('Admin/Employers/Index', [
            'employers' => $page,
            'selected' => $selected?->toAdminArray(),
            'jobs' => $selected ? $this->jobs($selected) : [],
            'applications' => $selected ? $this->applications($selected) : [],
            'submissions' => $selected ? $this->submissions($selected) : [],
            'selections' => $selected ? $this->selections($selected) : [],
            'placements' => $selected ? $this->placements($selected) : [],
            'employees' => $selected ? $this->employees($selected) : [],
            'replacements' => $selected ? $this->replacements($selected) : [],
            'documents' => $selected ? $this->documents($selected) : [],
            'document_types' => EmployerDocumentType::options(),
            'document_statuses' => FileLifecycleStatus::options(),
            'enquiries' => $selected ? $this->enquiries($selected) : [],
            'crm' => $selected ? $this->crm($selected) : [],
            'activities' => $selected ? $timeline->compose($selected) : [],
            'finance' => $selected ? $finance->summary($selected->id) : null,
            'invoices' => $selected ? $this->invoices($selected) : [],
            'payments' => $selected ? $this->payments($selected) : [],
            'filters' => $employers->filters($request),
            'industries' => Industry::query()->ordered()->get(['id', 'name'])->map(fn (Industry $industry) => [
                'id' => $industry->id,
                'name' => $industry->name,
            ])->all(),
            'accountStatuses' => array_map(fn (AccountStatus $status) => [
                'value' => $status->value,
                'label' => $status->label(),
            ], AccountStatus::cases()),
            'lookups' => [
                ...$masterData->formOptions(),
                'tags' => Tag::query()->orderBy('name')->get()->map(fn (Tag $tag) => $tag->toAdminArray())->all(),
                'locations' => Location::query()->ordered()->get(['id', 'name', 'is_active'])->map(fn (Location $location) => [
                    'id' => $location->id,
                    'name' => $location->name,
                    'is_active' => $location->is_active,
                ])->all(),
            ],
            'can_manage' => $actor?->hasPermission(PermissionName::EmployersManage->value) === true,
            'relationship_recruiter' => $selected ? $this->relationshipRecruiterPayload($selected) : null,
            'internal_recruiters' => OurNearInternalRecruiters::optionsForAdmin(),
            'can_assign_relationship_recruiter' => $actor?->can('create', CompanyRelationshipRecruiterAssignment::class) === true,
        ]);
    }

    public function assignRelationshipRecruiter(
        StoreCompanyRelationshipRecruiterAssignmentRequest $request,
        EmployerProfile $employer,
        CompanyRelationshipRecruiterAssignmentService $service,
    ): RedirectResponse {
        $recruiter = \App\Models\User::query()->findOrFail($request->integer('assigned_recruiter_user_id'));
        $service->assign($employer, $recruiter, $request->user(), $request->input('notes'));

        return back()->with('success', 'OurNear recruiter assigned to this company relationship.');
    }

    /**
     * @return array<string, mixed>
     */
    private function relationshipRecruiterPayload(EmployerProfile $employer): array
    {
        $service = app(CompanyRelationshipRecruiterAssignmentService::class);
        $active = $service->activeForEmployer($employer);

        return [
            'active' => $active?->toAdminArray(),
            'history' => $service->historyForEmployer($employer),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function jobs(EmployerProfile $employer): array
    {
        return $employer->jobs()
            ->withCount('applications')
            ->latest('updated_at')
            ->limit(20)
            ->get()
            ->map(fn ($job) => $job->toAdminArray())
            ->values()
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function applications(EmployerProfile $employer): array
    {
        return $employer->applications()
            ->with(['user.candidateProfile', 'job'])
            ->latest()
            ->limit(20)
            ->get()
            ->map(fn (Application $application) => [
                'id' => $application->id,
                'business_id' => $application->business_id,
                'status' => $application->status->value,
                'name' => $application->full_name,
                'candidate_id' => $application->user?->candidateProfile?->id,
                'candidate_business_id' => $application->user?->candidateProfile?->business_id,
                'job' => $application->job ? [
                    'id' => $application->job->id,
                    'business_id' => $application->job->business_id,
                    'title' => $application->job->title,
                    'slug' => $application->job->slug,
                ] : null,
                'workspace_url' => route('admin.applications.index', ['application' => $application->id], false),
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function submissions(EmployerProfile $employer): array
    {
        return $employer->submissions()
            ->with(['job', 'application.user.candidateProfile', 'placement'])
            ->latest('submitted_at')
            ->limit(20)
            ->get()
            ->map(fn (EmployerSubmission $submission) => $submission->toAdminListArray())
            ->values()
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function selections(EmployerProfile $employer): array
    {
        return $employer->selections()
            ->with(['job', 'application.user.candidateProfile', 'placement'])
            ->latest('decided_at')
            ->limit(20)
            ->get()
            ->map(fn (EmployerSubmission $submission) => $submission->toAdminListArray())
            ->values()
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function placements(EmployerProfile $employer): array
    {
        return $employer->placements()
            ->with(['job', 'application.user.candidateProfile', 'submission', 'offer', 'employee'])
            ->latest()
            ->limit(20)
            ->get()
            ->map(fn (Placement $placement) => $placement->toAdminListArray())
            ->values()
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function employees(EmployerProfile $employer): array
    {
        return $employer->employees()
            ->with(['job', 'application.user.candidateProfile', 'placement', 'candidateProfile'])
            ->latest('joined_at')
            ->limit(20)
            ->get()
            ->map(fn (Employee $employee) => $employee->toAdminListArray())
            ->values()
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function replacements(EmployerProfile $employer): array
    {
        return $employer->replacements()
            ->with([
                'job',
                'originalEmployee.application',
                'originalPlacement',
                'replacementApplication',
                'replacementEmployee.application',
            ])
            ->latest('requested_at')
            ->limit(20)
            ->get()
            ->map(fn (Replacement $replacement) => $replacement->toAdminListArray())
            ->values()
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function enquiries(EmployerProfile $employer): array
    {
        return $employer->enquiries()
            ->with('crmLead')
            ->latest()
            ->limit(20)
            ->get()
            ->map(fn ($enquiry) => $enquiry->toAdminArray())
            ->values()
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function crm(EmployerProfile $employer): array
    {
        return $employer->crmLeads()
            ->with(['opportunities', 'assignee:id,name'])
            ->latest('id')
            ->limit(20)
            ->get()
            ->map(fn ($lead) => $lead->toAdminArray())
            ->values()
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function invoices(EmployerProfile $employer): array
    {
        return $employer->invoices()
            ->with(['job', 'employee.application', 'payments'])
            ->latest('billed_at')
            ->limit(20)
            ->get()
            ->map(fn (Invoice $invoice) => $invoice->toAdminListArray())
            ->values()
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function payments(EmployerProfile $employer): array
    {
        return $employer->payments()
            ->with(['invoice', 'employer.user', 'recorder:id,name'])
            ->latest('paid_at')
            ->limit(20)
            ->get()
            ->map(fn (Payment $payment) => $payment->toAdminArray())
            ->values()
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function documents(EmployerProfile $employer): array
    {
        return $employer->documents()
            ->with('verifier')
            ->get()
            ->map(fn (EmployerDocument $document) => $document->toAdminArray())
            ->values()
            ->all();
    }

    private function documentFor(EmployerProfile $employer, EmployerDocument $document): EmployerDocument
    {
        abort_unless((int) $document->employer_profile_id === (int) $employer->id, 403);

        return $document;
    }

    public function approveStaffingAccess(
        DecideEmployerStaffingAccessRequest $request,
        EmployerProfile $employer,
        EmployerStaffingAccessService $staffingAccess,
    ): RedirectResponse {
        $this->authorize('update', $employer);
        $staffingAccess->approve($request->user(), $employer, $request->input('notes'));

        return back()->with('success', 'Staffing access approved for this company.');
    }

    public function rejectStaffingAccess(
        DecideEmployerStaffingAccessRequest $request,
        EmployerProfile $employer,
        EmployerStaffingAccessService $staffingAccess,
    ): RedirectResponse {
        $this->authorize('update', $employer);
        $staffingAccess->reject($request->user(), $employer, (string) $request->input('reason', ''));

        return back()->with('success', 'Staffing access request rejected.');
    }

    public function revokeStaffingAccess(
        DecideEmployerStaffingAccessRequest $request,
        EmployerProfile $employer,
        EmployerStaffingAccessService $staffingAccess,
    ): RedirectResponse {
        $this->authorize('update', $employer);
        $staffingAccess->revoke($request->user(), $employer, $request->input('notes'));

        return back()->with('success', 'Staffing access revoked for this company.');
    }

}
