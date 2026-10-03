<?php

namespace App\Policies;

use App\Enums\PermissionName;
use App\Enums\RelationshipReviewDirection;
use App\Enums\RelationshipReviewStatus;
use App\Models\Employee;
use App\Models\RelationshipReview;
use App\Models\User;
use App\Services\RelationshipReviewService;

class RelationshipReviewPolicy
{
    public function __construct(private RelationshipReviewService $reviews) {}

    public function view(User $user, RelationshipReview $review): bool
    {
        if ($this->staffCanView($user)) {
            return true;
        }

        if ($this->reviews->isReviewer($user, $review)) {
            return true;
        }

        if ($review->status === RelationshipReviewStatus::Hidden) {
            return false;
        }

        return $this->reviews->isCounterpart($user, $review);
    }

    public function createForEmployee(User $user, Employee $employee, RelationshipReviewDirection $direction): bool
    {
        return $this->reviews->canSubmit($user, $employee, $direction);
    }

    public function hide(User $user, RelationshipReview $review): bool
    {
        return $this->staffCanManage($user);
    }

    private function staffCanView(User $user): bool
    {
        return $user->hasPermission(PermissionName::EmployeesView->value)
            || $user->hasPermission(PermissionName::EmployeesManage->value);
    }

    private function staffCanManage(User $user): bool
    {
        return $user->hasPermission(PermissionName::EmployeesManage->value);
    }
}
