<?php

namespace App\Services;

use App\Enums\PlacementStatus;
use App\Enums\RecruitmentActivityType;
use App\Enums\RelationshipReviewDirection;
use App\Enums\RelationshipReviewStatus;
use App\Enums\RelationshipReviewVisibility;
use App\Enums\UserActivityType;
use App\Models\Employee;
use App\Models\RelationshipReview;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RelationshipReviewService
{
    public function __construct(
        private UserActivityService $userActivities,
        private RecruitmentActivityService $recruitmentActivities,
    ) {}

    public function isJoined(Employee $employee): bool
    {
        $employee->loadMissing('placement');

        return $employee->joined_at !== null
            && $employee->employer_profile_id !== null
            && $employee->candidate_profile_id !== null
            && $employee->placement?->status === PlacementStatus::Joined;
    }

    public function employerOwns(User $user, Employee $employee): bool
    {
        return $user->isEmployer()
            && $user->employerProfile !== null
            && (int) $employee->employer_profile_id === (int) $user->employerProfile->id;
    }

    public function workerOwns(User $user, Employee $employee): bool
    {
        return $user->isCandidate()
            && $user->candidateProfile !== null
            && (int) $employee->candidate_profile_id === (int) $user->candidateProfile->id;
    }

    public function isSelfReview(User $user, Employee $employee): bool
    {
        $employee->loadMissing(['employer', 'candidateProfile', 'application']);

        $employerUserId = $employee->employer?->user_id;
        $workerUserId = $employee->candidateProfile?->user_id ?? $employee->application?->user_id;

        if ($employerUserId === null || $workerUserId === null) {
            return true;
        }

        return (int) $employerUserId === (int) $workerUserId
            || ((int) $user->id === (int) $employerUserId && (int) $user->id === (int) $workerUserId);
    }

    public function canSubmit(User $user, Employee $employee, RelationshipReviewDirection $direction): bool
    {
        if (! $this->isJoined($employee) || $this->isSelfReview($user, $employee)) {
            return false;
        }

        if ($direction === RelationshipReviewDirection::EmployerToWorker) {
            return $this->employerOwns($user, $employee);
        }

        return $this->workerOwns($user, $employee);
    }

    public function isReviewer(User $user, RelationshipReview $review): bool
    {
        return (int) $review->reviewer_user_id === (int) $user->id;
    }

    public function isCounterpart(User $user, RelationshipReview $review): bool
    {
        $review->loadMissing('employee');
        $employee = $review->employee;

        if ($employee === null) {
            return false;
        }

        if ($review->direction === RelationshipReviewDirection::EmployerToWorker) {
            return $this->workerOwns($user, $employee);
        }

        return $this->employerOwns($user, $employee);
    }

    public function submit(
        User $reviewer,
        Employee $employee,
        RelationshipReviewDirection $direction,
        int $rating,
        string $body,
    ): RelationshipReview {
        $employee->loadMissing(['employer', 'candidateProfile', 'application', 'placement']);

        if (! $this->canSubmit($reviewer, $employee, $direction)) {
            abort(403);
        }

        if ($this->existing($employee, $direction) !== null) {
            throw ValidationException::withMessages([
                'employee' => 'A review already exists for this relationship.',
            ]);
        }

        try {
            $review = DB::transaction(function () use ($reviewer, $employee, $direction, $rating, $body) {
                $review = RelationshipReview::query()->create([
                    'employee_id' => $employee->id,
                    'employer_profile_id' => $employee->employer_profile_id,
                    'candidate_profile_id' => $employee->candidate_profile_id,
                    'reviewer_user_id' => $reviewer->id,
                    'direction' => $direction,
                    'rating' => $rating,
                    'body' => $body,
                    'visibility' => RelationshipReviewVisibility::Private,
                    'status' => RelationshipReviewStatus::Submitted,
                    'hidden_by' => null,
                    'hidden_at' => null,
                ]);

                $this->userActivities->record(
                    $reviewer,
                    UserActivityType::SubmittedRelationshipReview,
                    'Submitted a private relationship review.',
                    $reviewer,
                    [
                        'review_business_id' => $review->business_id,
                        'employee_id' => $employee->id,
                        'direction' => $direction->value,
                        'rating' => $rating,
                    ],
                );

                $this->recruitmentActivities->record(
                    actor: $reviewer,
                    type: RecruitmentActivityType::RelationshipReviewSubmitted,
                    application: $employee->application,
                    body: $direction->label().' review submitted (rating '.$rating.').',
                    employer: $employee->employer,
                    placement: $employee->placement,
                    employee: $employee,
                );

                return $review;
            });
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages([
                'employee' => 'A review already exists for this relationship.',
            ]);
        }

        return $review;
    }

    public function hide(User $actor, RelationshipReview $review): RelationshipReview
    {
        if ($review->status === RelationshipReviewStatus::Hidden) {
            return $review;
        }

        return DB::transaction(function () use ($actor, $review) {
            $review->status = RelationshipReviewStatus::Hidden;
            $review->hidden_by = $actor->id;
            $review->hidden_at = now();
            $review->save();

            $review->loadMissing(['employee.application', 'employee.employer', 'employee.placement', 'reviewer']);

            $this->recruitmentActivities->record(
                actor: $actor,
                type: RecruitmentActivityType::RelationshipReviewHidden,
                application: $review->employee?->application,
                body: 'Relationship review '.$review->business_id.' hidden.',
                employer: $review->employee?->employer,
                placement: $review->employee?->placement,
                employee: $review->employee,
            );

            if ($review->reviewer instanceof User) {
                $this->userActivities->record(
                    $review->reviewer,
                    UserActivityType::HiddenRelationshipReview,
                    'A private relationship review was hidden.',
                    $actor,
                    [
                        'review_business_id' => $review->business_id,
                        'employee_id' => $review->employee_id,
                    ],
                );
            }

            return $review->fresh() ?? $review;
        });
    }

    public function existing(Employee $employee, RelationshipReviewDirection $direction): ?RelationshipReview
    {
        return RelationshipReview::query()
            ->where('employee_id', $employee->id)
            ->where('direction', $direction->value)
            ->first();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function employerWorkspace(User $employer): array
    {
        $profile = $employer->employerProfile;
        if ($profile === null) {
            return [];
        }

        return Employee::query()
            ->where('employer_profile_id', $profile->id)
            ->whereNotNull('joined_at')
            ->whereNotNull('candidate_profile_id')
            ->with(['job', 'application', 'candidateProfile.user', 'placement', 'reviews'])
            ->latest('joined_at')
            ->latest('id')
            ->get()
            ->map(fn (Employee $employee) => $this->relationshipPayload(
                $employee,
                $employer,
                RelationshipReviewDirection::EmployerToWorker,
            ))
            ->values()
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function workerWorkspace(User $candidate): array
    {
        $profile = $candidate->candidateProfile;
        if ($profile === null) {
            return [];
        }

        return Employee::query()
            ->where('candidate_profile_id', $profile->id)
            ->whereNotNull('joined_at')
            ->whereNotNull('employer_profile_id')
            ->with(['job', 'employer.user', 'placement', 'reviews'])
            ->latest('joined_at')
            ->latest('id')
            ->get()
            ->map(fn (Employee $employee) => $this->relationshipPayload(
                $employee,
                $candidate,
                RelationshipReviewDirection::WorkerToEmployer,
            ))
            ->values()
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function adminReviews(Employee $employee): array
    {
        $employee->loadMissing(['reviews.reviewer:id,name', 'reviews.hiddenBy:id,name']);

        return $employee->reviews
            ->sortBy('id')
            ->map(fn (RelationshipReview $review) => $review->toAdminArray())
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function relationshipPayload(
        Employee $employee,
        User $viewer,
        RelationshipReviewDirection $ownDirection,
    ): array {
        $own = $employee->reviews->first(
            fn (RelationshipReview $review) => $review->direction === $ownDirection,
        );
        $counterpartDirection = $ownDirection === RelationshipReviewDirection::EmployerToWorker
            ? RelationshipReviewDirection::WorkerToEmployer
            : RelationshipReviewDirection::EmployerToWorker;
        $counterpart = $employee->reviews->first(
            fn (RelationshipReview $review) => $review->direction === $counterpartDirection,
        );

        $counterpartPayload = null;
        if ($counterpart instanceof RelationshipReview && $viewer->can('view', $counterpart)) {
            $counterpartPayload = $counterpart->toPartyArray();
        }

        return [
            'id' => $employee->id,
            'business_id' => $employee->business_id,
            'worker_name' => $employee->application?->full_name
                ?? $employee->candidateProfile?->user?->name,
            'employer_name' => $employee->employer?->displayCompany(),
            'job_title' => $employee->job?->title,
            'joined_at' => $employee->joined_at?->toDateTimeString(),
            'own_review' => $own?->toPartyArray(),
            'counterpart_review' => $counterpartPayload,
            'can_submit' => $this->canSubmit($viewer, $employee, $ownDirection) && $own === null,
        ];
    }
}
