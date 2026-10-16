<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RecruiterTalentPoolAssignmentProgressStage;
use App\Enums\RecruiterTalentPoolAssignmentType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreRecruiterTalentPoolAssignmentRequest;
use App\Http\Requests\Admin\StoreTalentPoolCompanySubmissionRequest;
use App\Http\Requests\Account\UpdateRecruiterTalentPoolAssignmentRequest;
use App\Models\Application;
use App\Models\CandidateProfile;
use App\Models\CandidateResume;
use App\Models\EmployerEnquiry;
use App\Models\EmployerProfile;
use App\Models\Job;
use App\Models\RecruiterTalentPoolAssignment;
use App\Models\User;
use App\Services\RecruiterTalentPoolAssignmentService;
use App\Support\CandidateQuery;
use App\Support\OurNearHireIdentity;
use App\Models\CompanyRelationshipRecruiterAssignment;
use App\Models\StaffingCandidateSubmission;
use App\Models\TalentPoolCompanySubmission;
use App\Services\CompanyRelationshipRecruiterAssignmentService;
use App\Services\TalentPoolCompanySubmissionService;
use App\Support\OurNearInternalRecruiters;
use App\Support\StaffingRequirementAllocationQuery;
use App\Support\StaffOptions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class RecruiterTalentPoolAssignmentsController extends Controller
{
    public function index(Request $request, CandidateQuery $candidates): Response
    {
        $this->authorize('viewAny', RecruiterTalentPoolAssignment::class);

        $status = trim((string) $request->query('status', ''));
        $actor = $request->user();
        $recruiterId = $request->integer('recruiter_user_id');
        $workspaceScoped = $actor !== null && OurNearInternalRecruiters::shouldScopeTalentPoolWorkspace($actor);
        if ($recruiterId <= 0 && $workspaceScoped) {
            $recruiterId = (int) $actor->id;
        }
        $assignmentType = trim((string) $request->query('assignment_type', ''));
        $employerId = $request->integer('employer_id');
        $followUpDue = $request->boolean('follow_up_due');
        $assignmentHighlight = $request->integer('assignment');

        $assignmentQuery = RecruiterTalentPoolAssignment::query()
            ->with([
                'candidateUser.candidateProfile',
                'recruiterUser.employerProfile',
                'assignedByUser',
                'job.employer',
                'employerEnquiry',
            ])
            ->latest('id');

        if ($status !== '') {
            $assignmentQuery->where('status', $status);
        }
        if ($recruiterId > 0) {
            $assignmentQuery->where('assigned_recruiter_user_id', $recruiterId);
        }
        if ($assignmentType !== '') {
            $assignmentQuery->where('assignment_type', $assignmentType);
        }
        if ($employerId > 0) {
            $assignmentQuery->whereHas('job', fn ($job) => $job->where('employer_id', $employerId));
        }
        if ($followUpDue) {
            $assignmentQuery
                ->whereNotNull('follow_up_at')
                ->where('follow_up_at', '<', now())
                ->whereNotIn('status', [
                    \App\Enums\RecruiterTalentPoolAssignmentStatus::Completed->value,
                    \App\Enums\RecruiterTalentPoolAssignmentStatus::Declined->value,
                    \App\Enums\RecruiterTalentPoolAssignmentStatus::Returned->value,
                ]);
        }
        if ($request->boolean('archived')) {
            $assignmentQuery->whereNotNull('archived_at');
        } else {
            $assignmentQuery->whereNull('archived_at');
        }

        $assignments = $assignmentQuery
            ->paginate(20, ['*'], 'assignments_page')
            ->withQueryString()
            ->through(fn (RecruiterTalentPoolAssignment $assignment) => $assignment->toAdminListArray());

        if ($workspaceScoped && $actor !== null) {
            $request->merge(['assigned_recruiter_user_id' => (string) $actor->id]);
        }

        $candidatePage = $candidates->adminIndex($request, 20);
        $userIds = $candidatePage->getCollection()->pluck('user_id')->filter()->all();
        $activeByUser = RecruiterTalentPoolAssignment::query()
            ->whereIn('candidate_user_id', $userIds)
            ->whereNull('archived_at')
            ->whereIn('status', [
                \App\Enums\RecruiterTalentPoolAssignmentStatus::PendingReview->value,
                \App\Enums\RecruiterTalentPoolAssignmentStatus::InProgress->value,
            ])
            ->with(['recruiterUser', 'statusEvents' => fn ($q) => $q->latest()->limit(1)])
            ->get()
            ->groupBy('candidate_user_id');

        $resumeUserIds = CandidateResume::query()
            ->whereHas('candidateProfile', fn ($q) => $q->whereIn('user_id', $userIds))
            ->join('candidate_profiles', 'candidate_profiles.id', '=', 'candidate_resumes.candidate_profile_id')
            ->pluck('candidate_profiles.user_id')
            ->unique()
            ->all();

        $identity = app(OurNearHireIdentity::class);

        $candidateRows = $candidatePage->through(function (CandidateProfile $profile) use ($activeByUser, $resumeUserIds, $identity) {
            $profile->loadMissing(['preferredLocations', 'user']);
            $userId = (int) $profile->user_id;
            /** @var RecruiterTalentPoolAssignment|null $active */
            $active = $activeByUser->get($userId)?->sortByDesc('id')->first();

            return [
                'profile_id' => $profile->id,
                'user_id' => $userId,
                'name' => $profile->user?->name,
                'ournear_id' => $profile->user ? $identity->display($profile->user, 'CAN') : null,
                'email' => $profile->user?->email,
                'mobile' => $profile->mobile ?: $profile->user?->mobile,
                'skills' => $profile->skillsDisplay(),
                'experience' => $profile->classifiedExperienceRange?->name ?: $profile->experience,
                'preferred_location' => $profile->preferredLocations->first()?->name
                    ?? ($profile->classifiedLocation?->name ?: $profile->location),
                'has_resume' => in_array($userId, $resumeUserIds, true),
                'allocation_status' => $active?->progressStageLabel() ?? 'Unassigned',
                'assigned_recruiter' => $active?->recruiterUser?->name,
                'active_assignment_id' => $active?->id,
                'last_activity' => $active?->updated_at?->toDateTimeString()
                    ?? $profile->updated_at?->toDateTimeString(),
                'candidate_workspace_url' => route('admin.candidates.index', ['candidate' => $profile->id], false),
            ];
        });

        $recruiters = OurNearInternalRecruiters::optionsForAdmin();

        $employers = EmployerProfile::query()
            ->orderBy('company_name')
            ->get(['id', 'company_name'])
            ->map(fn (EmployerProfile $employer) => [
                'id' => $employer->id,
                'name' => $employer->displayCompany(),
            ])
            ->values()
            ->all();

        $jobs = Job::query()
            ->orderBy('title')
            ->get(['id', 'title', 'employer_id'])
            ->map(fn (Job $job) => [
                'id' => $job->id,
                'title' => $job->title,
                'employer_id' => $job->employer_id,
            ])
            ->values()
            ->all();

        $staffingRequirements = StaffingRequirementAllocationQuery::optionsForAdminAllocation();
        $staffingCompanies = StaffingRequirementAllocationQuery::companyDirectoryForAdmin();

        $selectedAssignment = null;
        if ($assignmentHighlight > 0) {
            $selected = RecruiterTalentPoolAssignment::query()->find($assignmentHighlight);
            if ($selected !== null) {
                $this->authorize('view', $selected);
                $selectedAssignment = $selected->toAdminDetailArray();
            }
        }

        $companyEmployerId = $request->integer('company_employer_id');
        $companyActive = null;
        $companyHistory = [];
        if ($companyEmployerId > 0) {
            $companyEmployer = EmployerProfile::query()->find($companyEmployerId);
            if ($companyEmployer !== null) {
                $this->authorize('view', $companyEmployer);
                $service = app(CompanyRelationshipRecruiterAssignmentService::class);
                $active = $service->activeForEmployer($companyEmployer);
                $companyActive = $active?->toAdminArray();
                $companyHistory = $service->historyForEmployer($companyEmployer);
            }
        }

        return Inertia::render('Admin/TalentPoolAssignments/Index', [
            'candidates' => $candidateRows,
            'assignments' => $assignments,
            'selected_assignment' => $selectedAssignment,
            'recruiters' => $recruiters,
            'employers' => $employers,
            'jobs' => $jobs,
            'staffing_requirements' => $staffingRequirements,
            'staffing_companies' => $staffingCompanies,
            'can_manage' => $request->user()?->can('create', RecruiterTalentPoolAssignment::class) ?? false,
            'filters' => [
                'search' => $request->string('search')->toString(),
                'location_id' => $request->string('location_id')->toString(),
                'skill_id' => $request->string('skill_id')->toString(),
                'status' => $status,
                'recruiter_user_id' => $recruiterId > 0 ? (string) $recruiterId : '',
                'assignment_type' => $assignmentType,
                'employer_id' => $employerId > 0 ? (string) $employerId : '',
                'follow_up_due' => $followUpDue,
                'archived' => $request->boolean('archived'),
                'assignment' => $assignmentHighlight > 0 ? (string) $assignmentHighlight : '',
                'company_employer_id' => $companyEmployerId > 0 ? (string) $companyEmployerId : '',
            ],
            'assignment_types' => array_map(
                fn (RecruiterTalentPoolAssignmentType $type) => ['value' => $type->value, 'label' => $type->label()],
                RecruiterTalentPoolAssignmentType::cases(),
            ),
            'progress_stages' => array_map(
                fn (RecruiterTalentPoolAssignmentProgressStage $stage) => ['value' => $stage->value, 'label' => $stage->label()],
                RecruiterTalentPoolAssignmentProgressStage::cases(),
            ),
            'statuses' => array_map(
                fn (\App\Enums\RecruiterTalentPoolAssignmentStatus $statusEnum) => [
                    'value' => $statusEnum->value,
                    'label' => $statusEnum->label(),
                ],
                \App\Enums\RecruiterTalentPoolAssignmentStatus::cases(),
            ),
            'staffUsers' => StaffOptions::users(),
            'company_relationship' => [
                'employers' => $employers,
                'recruiters' => $recruiters,
                'selected_employer_id' => $companyEmployerId > 0 ? $companyEmployerId : null,
                'active' => $companyActive,
                'history' => $companyHistory,
                'can_manage' => $request->user()?->can('create', CompanyRelationshipRecruiterAssignment::class) ?? false,
            ],
            'recruiter_workspace_scoped' => $workspaceScoped,
            'company_submissions' => TalentPoolCompanySubmission::query()
                ->when($workspaceScoped && $actor !== null, fn ($q) => $q->where('responsible_recruiter_user_id', $actor->id))
                ->when($recruiterId > 0 && ! $workspaceScoped, fn ($q) => $q->where('responsible_recruiter_user_id', $recruiterId))
                ->latest('submitted_at')
                ->limit(50)
                ->get()
                ->map(fn (TalentPoolCompanySubmission $row) => $row->toAdminListArray())
                ->values()
                ->all(),
            'can_submit_to_company' => $request->user()?->can('create', TalentPoolCompanySubmission::class) ?? false,
        ]);
    }

    public function storeCompanySubmission(
        StoreTalentPoolCompanySubmissionRequest $request,
        TalentPoolCompanySubmissionService $service,
    ): RedirectResponse {
        $candidate = User::query()->findOrFail($request->integer('candidate_user_id'));
        $employer = EmployerProfile::query()->findOrFail($request->integer('employer_profile_id'));
        $assignment = $request->filled('recruiter_talent_pool_assignment_id')
            ? RecruiterTalentPoolAssignment::query()->find($request->integer('recruiter_talent_pool_assignment_id'))
            : null;
        $explicitRecruiter = $request->filled('responsible_recruiter_user_id')
            ? User::query()->find($request->integer('responsible_recruiter_user_id'))
            : null;

        $submission = $service->submit(
            $candidate,
            $employer,
            $request->user(),
            $assignment,
            $explicitRecruiter,
            $request->input('internal_notes'),
        );

        return redirect()
            ->route('admin.talent-pool-assignments.index', [
                'company_employer_id' => $employer->id,
            ])
            ->with('success', 'Candidate submitted to '.$employer->displayCompany().' (Talent Pool). Reference: '.$submission->business_id);
    }

    public function submitAssignmentToCompany(
        Request $request,
        RecruiterTalentPoolAssignment $assignment,
        RecruiterTalentPoolAssignmentService $service,
    ): RedirectResponse {
        $this->authorize('create', TalentPoolCompanySubmission::class);
        $this->authorize('view', $assignment);

        $validated = $request->validate([
            'employer_profile_id' => ['required', 'integer', 'exists:employer_profiles,id'],
            'internal_notes' => ['nullable', 'string', 'max:5000'],
            'responsible_recruiter_user_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $employer = EmployerProfile::query()->findOrFail((int) $validated['employer_profile_id']);
        $explicit = isset($validated['responsible_recruiter_user_id'])
            ? User::query()->find((int) $validated['responsible_recruiter_user_id'])
            : null;

        $submission = $service->submitToCompany(
            $assignment,
            $employer,
            $request->user(),
            $explicit,
            $validated['internal_notes'] ?? null,
        );

        return back()->with('success', 'Candidate submitted to company. Reference: '.$submission->business_id);
    }

    public function submitAssignmentToStaffingClient(
        Request $request,
        RecruiterTalentPoolAssignment $assignment,
        RecruiterTalentPoolAssignmentService $service,
    ): RedirectResponse {
        $this->authorize('submitToClient', StaffingCandidateSubmission::class);
        $this->authorize('view', $assignment);

        $validated = $request->validate([
            'internal_notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $submission = $service->submitToStaffingClient(
            $assignment,
            $request->user(),
            $validated['internal_notes'] ?? null,
        );

        return back()->with('success', 'Candidate submitted to client for staffing. Reference: '.$submission->business_id);
    }

    public function archive(
        Request $request,
        RecruiterTalentPoolAssignment $assignment,
        RecruiterTalentPoolAssignmentService $service,
    ): RedirectResponse {
        $this->authorize('archive', $assignment);

        $service->archive($assignment, $request->user());

        return redirect()
            ->route('admin.talent-pool-assignments.index')
            ->with('success', 'Assignment archived. Candidate records are preserved.');
    }

    public function updateStatus(
        UpdateRecruiterTalentPoolAssignmentRequest $request,
        RecruiterTalentPoolAssignment $assignment,
        RecruiterTalentPoolAssignmentService $service,
    ): RedirectResponse {
        $user = $request->user();
        abort_unless($user !== null, 403);

        if ($request->filled('progress_stage')) {
            $stage = $request->enum('progress_stage', RecruiterTalentPoolAssignmentProgressStage::class);
            abort_unless($stage instanceof RecruiterTalentPoolAssignmentProgressStage, 422);

            $service->updateProgress(
                $assignment,
                $user,
                $stage,
                $request->input('notes'),
                $request->filled('follow_up_at') ? Carbon::parse($request->input('follow_up_at')) : null,
                $request->filled('interview_at') ? Carbon::parse($request->input('interview_at')) : null,
                $request->input('interview_details'),
            );
        } else {
            $status = $request->enum('status', \App\Enums\RecruiterTalentPoolAssignmentStatus::class);
            abort_unless($status instanceof \App\Enums\RecruiterTalentPoolAssignmentStatus, 422);

            $service->transition(
                $assignment,
                $user,
                $status,
                $request->input('notes'),
            );
        }

        return back()->with('success', 'Assignment progress updated.');
    }

    public function store(
        StoreRecruiterTalentPoolAssignmentRequest $request,
        RecruiterTalentPoolAssignmentService $service,
    ): RedirectResponse {
        $recruiter = User::query()->findOrFail($request->integer('assigned_recruiter_user_id'));
        $type = $request->enum('assignment_type', RecruiterTalentPoolAssignmentType::class);
        abort_unless($type instanceof RecruiterTalentPoolAssignmentType, 422);

        $application = $request->filled('application_id')
            ? Application::query()->find($request->integer('application_id'))
            : null;
        $job = $request->filled('job_id') ? Job::query()->find($request->integer('job_id')) : null;
        $enquiry = $request->filled('employer_enquiry_id')
            ? EmployerEnquiry::query()->find($request->integer('employer_enquiry_id'))
            : null;

        if (in_array($type, [
            RecruiterTalentPoolAssignmentType::TalentPool,
            RecruiterTalentPoolAssignmentType::General,
        ], true)) {
            $job = null;
            $enquiry = null;
            $application = null;
        }
        $followUp = $request->filled('follow_up_at')
            ? Carbon::parse($request->input('follow_up_at'))
            : null;

        $candidateIds = $request->filled('candidate_user_ids')
            ? array_map('intval', $request->input('candidate_user_ids', []))
            : [$request->integer('candidate_user_id')];

        $created = $service->assignMany(
            $request->user(),
            $candidateIds,
            $recruiter,
            $type,
            $application,
            $job,
            $enquiry,
            $request->input('admin_notes'),
            $followUp,
        );

        $count = count($created);
        $message = $count === 1
            ? 'Candidate assigned to recruiter.'
            : "{$count} candidates assigned to recruiter.";

        if ($request->filled('return_candidate_profile_id')) {
            return redirect()
                ->route('admin.candidates.index', [
                    'candidate' => $request->integer('return_candidate_profile_id'),
                ])
                ->with('success', $message);
        }

        $first = $created[0] ?? null;

        return redirect()
            ->route('admin.talent-pool-assignments.index', [
                'assignment' => $first?->id,
            ])
            ->with('success', $message);
    }
}
