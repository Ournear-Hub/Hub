<?php

namespace App\Http\Controllers\Admin;

use App\Enums\EmployerEnquiryStatus;
use App\Enums\RecruiterTalentPoolAssignmentType;
use App\Enums\StaffingCandidateSubmissionStatus;
use App\Http\Controllers\Controller;
use App\Models\EmployerEnquiry;
use App\Models\RecruiterTalentPoolAssignment;
use App\Models\StaffingCandidateSubmission;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StaffingOperationsController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', StaffingCandidateSubmission::class);

        $openRequirements = EmployerEnquiry::query()
            ->whereIn('status', [
                EmployerEnquiryStatus::New->value,
                EmployerEnquiryStatus::Contacted->value,
                EmployerEnquiryStatus::InProgress->value,
            ])
            ->count();

        $activeSubmissions = StaffingCandidateSubmission::query()
            ->whereNotIn('status', array_map(
                fn (StaffingCandidateSubmissionStatus $s) => $s->value,
                array_filter(
                    StaffingCandidateSubmissionStatus::cases(),
                    fn (StaffingCandidateSubmissionStatus $s) => $s->isTerminal(),
                ),
            ))
            ->count();

        $awaitingClient = StaffingCandidateSubmission::query()
            ->whereIn('status', [
                StaffingCandidateSubmissionStatus::SubmittedToClient->value,
                StaffingCandidateSubmissionStatus::UnderClientReview->value,
                StaffingCandidateSubmissionStatus::InterviewInProgress->value,
            ])
            ->count();

        $joinedTotal = StaffingCandidateSubmission::query()
            ->where('status', StaffingCandidateSubmissionStatus::JoinedConfirmed->value)
            ->count();

        $recentSubmissions = StaffingCandidateSubmission::query()
            ->with(['candidateUser', 'employerEnquiry', 'responsibleRecruiter'])
            ->latest('submitted_at')
            ->limit(15)
            ->get()
            ->map(fn (StaffingCandidateSubmission $row) => $row->toAdminListArray())
            ->values()
            ->all();

        $openAssignments = RecruiterTalentPoolAssignment::query()
            ->where('assignment_type', RecruiterTalentPoolAssignmentType::Staffing->value)
            ->whereNull('archived_at')
            ->whereNull('staffing_candidate_submission_id')
            ->count();

        return Inertia::render('Admin/StaffingOperations/Index', [
            'metrics' => [
                'open_requirements' => $openRequirements,
                'sourcing_in_progress' => $openAssignments,
                'active_submissions' => $activeSubmissions,
                'awaiting_client' => $awaitingClient,
                'joined_total' => $joinedTotal,
            ],
            'recent_submissions' => $recentSubmissions,
            'links' => [
                'enquiries' => route('admin.employer-enquiries.index', [], false),
                'allocations' => route('admin.talent-pool-assignments.index', ['assignment_type' => 'staffing'], false),
            ],
        ]);
    }
}
