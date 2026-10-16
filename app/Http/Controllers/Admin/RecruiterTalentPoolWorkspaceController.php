<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RecruiterTalentPoolAssignmentStatus;
use App\Enums\RecruitmentActivityType;
use App\Enums\RecruitmentOperationalVisibility;
use App\Http\Controllers\Controller;
use App\Models\RecruiterTalentPoolAssignment;
use App\Models\RecruitmentActivity;
use App\Models\TalentPoolCompanySubmission;
use App\Models\User;
use App\Support\OurNearInternalRecruiters;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RecruiterTalentPoolWorkspaceController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);
        abort_unless(OurNearInternalRecruiters::canActAsInternalRecruiter($user), 403);

        $scoped = OurNearInternalRecruiters::shouldScopeTalentPoolWorkspace($user);
        $recruiterId = $scoped ? (int) $user->id : max(0, $request->integer('recruiter_user_id'));

        $assignmentsQuery = RecruiterTalentPoolAssignment::query()
            ->with('candidateUser')
            ->whereNull('archived_at')
            ->whereIn('status', [
                RecruiterTalentPoolAssignmentStatus::PendingReview->value,
                RecruiterTalentPoolAssignmentStatus::InProgress->value,
            ]);
        if ($recruiterId > 0) {
            $assignmentsQuery->where('assigned_recruiter_user_id', $recruiterId);
        }
        $assignedCandidates = $assignmentsQuery->latest('id')->limit(12)->get()
            ->map(fn (RecruiterTalentPoolAssignment $row) => [
                'id' => $row->id,
                'candidate_name' => $row->candidateUser?->name,
                'status_label' => $row->status->label(),
                'follow_up_at' => $row->follow_up_at?->toDateTimeString(),
                'follow_up_overdue' => $row->follow_up_at !== null && $row->follow_up_at->isPast(),
            ])->values()->all();

        $submissionsQuery = TalentPoolCompanySubmission::query()
            ->with(['candidateUser', 'employerProfile'])
            ->latest('submitted_at');
        if ($recruiterId > 0) {
            $submissionsQuery->where('responsible_recruiter_user_id', $recruiterId);
        }
        $submissions = $submissionsQuery->limit(12)->get()
            ->map(fn (TalentPoolCompanySubmission $row) => [
                ...$row->toAdminListArray(),
                'show_href' => route('admin.talent-pool-company-submissions.show', $row, false),
            ])->values()->all();

        $overdueFollowUps = RecruiterTalentPoolAssignment::query()
            ->when($recruiterId > 0, fn ($q) => $q->where('assigned_recruiter_user_id', $recruiterId))
            ->whereNull('archived_at')
            ->whereNotNull('follow_up_at')
            ->where('follow_up_at', '<', now())
            ->count();

        $operationalDue = RecruitmentActivity::query()
            ->where('type', RecruitmentActivityType::TalentPoolFollowUpRecorded->value)
            ->where('visibility', RecruitmentOperationalVisibility::Internal->value)
            ->when($recruiterId > 0, fn ($q) => $q->where('actor_id', $recruiterId))
            ->latest('id')
            ->limit(200)
            ->get()
            ->filter(function (RecruitmentActivity $row) {
                $due = is_array($row->operational_meta) ? ($row->operational_meta['next_action_due_at'] ?? null) : null;
                if (! is_string($due) || $due === '') {
                    return false;
                }

                return \Illuminate\Support\Carbon::parse($due)->isPast();
            })
            ->count();

        return Inertia::render('Admin/TalentPoolWorkspace/Index', [
            'recruiter_workspace_scoped' => $scoped,
            'recruiter_user_id' => $recruiterId > 0 ? $recruiterId : null,
            'overview' => [
                'active_assignments_count' => count($assignedCandidates),
                'submissions_count' => count($submissions),
                'overdue_assignment_follow_ups' => $overdueFollowUps,
                'overdue_operational_next_actions' => $operationalDue,
            ],
            'assigned_candidates' => $assignedCandidates,
            'submissions' => $submissions,
            'links' => [
                'assignments' => route('admin.talent-pool-assignments.index', [], false),
                'messages' => route('admin.messages.index', [], false),
            ],
        ]);
    }
}
