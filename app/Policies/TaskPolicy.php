<?php

namespace App\Policies;

use App\Enums\PermissionName;
use App\Models\Task;
use App\Models\User;

class TaskPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->canViewApplications($user)
            || $this->canViewJobs($user)
            || $this->canViewEmployers($user)
            || $this->canViewFinance($user)
            || $this->canViewInvestors($user)
            || $this->canViewCampaigns($user);
    }

    public function view(User $user, Task $task): bool
    {
        if ($this->isActor($user, $task) && $this->viewAny($user)) {
            return true;
        }

        if ($task->isCommissionLinked()) {
            return $this->canViewFinance($user) || $this->canViewEmployers($user) || $this->canViewApplications($user);
        }

        if ($task->isInvestorLinked()) {
            return $this->canViewInvestors($user);
        }

        if ($task->isCampaignLinked()) {
            return $this->canViewCampaigns($user);
        }

        if ($task->isEmployerLinked()) {
            return $this->canViewEmployers($user) || $this->canViewApplications($user);
        }

        if ($task->isJobOnly()) {
            return $this->canViewJobs($user);
        }

        return $this->canViewApplications($user);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission(PermissionName::ApplicationsManage->value)
            || $user->hasPermission(PermissionName::JobsManage->value)
            || $user->hasPermission(PermissionName::EmployersManage->value)
            || $user->hasPermission(PermissionName::FinanceManage->value)
            || $user->hasPermission(PermissionName::InvestorsManage->value)
            || $user->hasPermission(PermissionName::CampaignsManage->value);
    }

    public function update(User $user, Task $task): bool
    {
        if ($task->isCommissionLinked()) {
            return $user->hasPermission(PermissionName::FinanceManage->value)
                || $user->hasPermission(PermissionName::EmployersManage->value)
                || $user->hasPermission(PermissionName::ApplicationsManage->value);
        }

        if ($task->isInvestorLinked()) {
            return $user->hasPermission(PermissionName::InvestorsManage->value);
        }

        if ($task->isCampaignLinked()) {
            return $user->hasPermission(PermissionName::CampaignsManage->value);
        }

        if ($task->isEmployerLinked()) {
            return $user->hasPermission(PermissionName::EmployersManage->value)
                || $user->hasPermission(PermissionName::ApplicationsManage->value);
        }

        if ($task->isJobOnly()) {
            return $user->hasPermission(PermissionName::JobsManage->value)
                || $user->hasPermission(PermissionName::ApplicationsManage->value);
        }

        return $user->hasPermission(PermissionName::ApplicationsManage->value);
    }

    public function complete(User $user, Task $task): bool
    {
        if ($this->update($user, $task)) {
            return true;
        }

        return $this->isAssignee($user, $task) && $this->view($user, $task);
    }

    private function canViewApplications(User $user): bool
    {
        return $user->hasPermission(PermissionName::ApplicationsView->value)
            || $user->hasPermission(PermissionName::ApplicationsManage->value);
    }

    private function canViewJobs(User $user): bool
    {
        return $user->hasPermission(PermissionName::JobsView->value)
            || $user->hasPermission(PermissionName::JobsManage->value);
    }

    private function canViewEmployers(User $user): bool
    {
        return $user->hasPermission(PermissionName::EmployersView->value)
            || $user->hasPermission(PermissionName::EmployersManage->value);
    }

    private function canViewFinance(User $user): bool
    {
        return $user->hasPermission(PermissionName::FinanceView->value)
            || $user->hasPermission(PermissionName::FinanceManage->value);
    }

    private function canViewInvestors(User $user): bool
    {
        return $user->hasPermission(PermissionName::InvestorsView->value)
            || $user->hasPermission(PermissionName::InvestorsManage->value);
    }

    private function canViewCampaigns(User $user): bool
    {
        return $user->hasPermission(PermissionName::CampaignsView->value)
            || $user->hasPermission(PermissionName::CampaignsManage->value);
    }

    private function isAssignee(User $user, Task $task): bool
    {
        return $task->assigned_to !== null && (int) $task->assigned_to === (int) $user->id;
    }

    private function isActor(User $user, Task $task): bool
    {
        return $this->isAssignee($user, $task)
            || (int) $task->created_by === (int) $user->id;
    }
}
