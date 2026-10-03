<?php

namespace App\Policies;

use App\Enums\PermissionName;
use App\Models\EmployerDocument;
use App\Models\EmployerProfile;
use App\Models\User;

class EmployerDocumentPolicy
{
    public function view(User $user, EmployerDocument $document): bool
    {
        return $this->staffCanView($user) || $this->ownedByEmployer($user, $document);
    }

    public function download(User $user, EmployerDocument $document): bool
    {
        return $this->view($user, $document);
    }

    public function create(User $user, EmployerProfile $employerProfile): bool
    {
        if ($this->staffCanManage($user)) {
            return true;
        }

        return $this->ownsProfile($user, $employerProfile);
    }

    public function update(User $user, EmployerDocument $document): bool
    {
        if ($this->staffCanManage($user)) {
            return true;
        }

        return $this->ownedByEmployer($user, $document);
    }

    public function delete(User $user, EmployerDocument $document): bool
    {
        return $this->update($user, $document);
    }

    public function verify(User $user, EmployerDocument $document): bool
    {
        return $this->staffCanManage($user) && $this->belongsToProfile($document);
    }

    private function staffCanView(User $user): bool
    {
        return $user->hasPermission(PermissionName::EmployersView->value)
            || $user->hasPermission(PermissionName::EmployersManage->value);
    }

    private function staffCanManage(User $user): bool
    {
        return $user->hasPermission(PermissionName::EmployersManage->value);
    }

    private function ownedByEmployer(User $user, EmployerDocument $document): bool
    {
        $profile = $document->employerProfile ?? $document->employerProfile()->first();

        return $profile instanceof EmployerProfile && $this->ownsProfile($user, $profile);
    }

    private function ownsProfile(User $user, EmployerProfile $employerProfile): bool
    {
        return $user->canAccessHiringWorkspace() && (int) $employerProfile->user_id === (int) $user->id;
    }

    private function belongsToProfile(EmployerDocument $document): bool
    {
        return $document->employerProfile()->exists();
    }
}
