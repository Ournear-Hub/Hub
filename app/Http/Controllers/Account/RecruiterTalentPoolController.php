<?php

namespace App\Http\Controllers\Account;

use App\Enums\RecruiterTalentPoolAssignmentProgressStage;
use App\Enums\RecruiterTalentPoolAssignmentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Account\UpdateRecruiterTalentPoolAssignmentRequest;
use App\Models\Application;
use App\Models\EmployerProfile;
use App\Models\RecruiterTalentPoolAssignment;
use App\Services\ApplicationResumeDeliveryService;
use App\Services\RecruiterTalentPoolAssignmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RecruiterTalentPoolController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', RecruiterTalentPoolAssignment::class);

        $user = $request->user();
        abort_unless($user !== null, 403);

        $status = trim((string) $request->query('status', ''));

        $profile = $user->employerProfile;

        $query = RecruiterTalentPoolAssignment::query()
            ->whereNull('archived_at')
            ->where(function ($builder) use ($user, $profile) {
                $builder->where('assigned_recruiter_user_id', $user->id);
                if ($profile !== null) {
                    $employerId = (int) $profile->id;
                    $builder->orWhereHas('job', fn ($job) => $job->where('employer_id', $employerId))
                        ->orWhereHas('employerEnquiry', fn ($enquiry) => $enquiry->where('user_id', $user->id));
                }
            })
            ->with(['candidateUser.candidateProfile', 'recruiterUser.candidateProfile', 'job', 'employerEnquiry', 'assignedByUser'])
            ->latest('id');

        if ($status !== '') {
            $query->where('status', $status);
        }

        $assignments = $query
            ->paginate(15)
            ->withQueryString()
            ->through(fn (RecruiterTalentPoolAssignment $assignment) => $assignment->toRecruiterListArray());

        return Inertia::render('Account/TalentPool/Index', [
            'company_name' => $profile instanceof EmployerProfile ? $profile->displayCompany() : '',
            'assignments' => $assignments,
            'filters' => [
                'status' => $status,
            ],
            'statuses' => array_map(
                fn (RecruiterTalentPoolAssignmentStatus $case) => [
                    'value' => $case->value,
                    'label' => $case->label(),
                ],
                RecruiterTalentPoolAssignmentStatus::cases(),
            ),
            'progress_stages' => array_map(
                fn (RecruiterTalentPoolAssignmentProgressStage $stage) => [
                    'value' => $stage->value,
                    'label' => $stage->label(),
                ],
                RecruiterTalentPoolAssignmentProgressStage::cases(),
            ),
        ]);
    }

    public function show(Request $request, RecruiterTalentPoolAssignment $assignment): Response
    {
        $this->authorize('view', $assignment);

        $assignment->load([
            'candidateUser.candidateProfile',
            'job',
            'employerEnquiry',
            'assignedByUser',
        ]);

        return Inertia::render('Account/TalentPool/Show', [
            'assignment' => $assignment->toRecruiterDetailArray(),
        ]);
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
                $request->filled('follow_up_at') ? \Illuminate\Support\Carbon::parse($request->input('follow_up_at')) : null,
                $request->filled('interview_at') ? \Illuminate\Support\Carbon::parse($request->input('interview_at')) : null,
                $request->input('interview_details'),
            );
        } else {
            $status = $request->enum('status', RecruiterTalentPoolAssignmentStatus::class);
            abort_unless($status instanceof RecruiterTalentPoolAssignmentStatus, 422);

            $service->transition(
                $assignment,
                $user,
                $status,
                $request->input('notes'),
            );
        }

        return back()->with('success', 'Assignment progress updated.');
    }

    public function submitToEmployer(
        Request $request,
        RecruiterTalentPoolAssignment $assignment,
        RecruiterTalentPoolAssignmentService $service,
    ): RedirectResponse {
        $this->authorize('updateStatus', $assignment);

        $user = $request->user();
        abort_unless($user !== null, 403);

        $service->submitToEmployer($assignment, $user);

        return back()->with('success', 'Candidate submitted to employer for review.');
    }

    public function archive(
        Request $request,
        RecruiterTalentPoolAssignment $assignment,
        RecruiterTalentPoolAssignmentService $service,
    ): RedirectResponse {
        $this->authorize('archive', $assignment);

        $service->archive($assignment, $request->user());

        return redirect()
            ->route('account.talent-pool.index')
            ->with('success', 'Assignment archived. Candidate records are preserved.');
    }

    public function resume(
        Request $request,
        RecruiterTalentPoolAssignment $assignment,
        ApplicationResumeDeliveryService $resumes,
    ): StreamedResponse {
        $this->authorize('downloadResume', $assignment);

        $application = Application::query()->findOrFail($assignment->application_id);
        $this->authorize('downloadResume', $application);

        $label = $application->business_id ?: 'application-'.$application->id;

        return $resumes->stream($request, $application, $label);
    }
}
