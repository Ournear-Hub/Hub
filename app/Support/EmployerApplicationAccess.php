<?php

namespace App\Support;

use App\Models\Application;
use App\Models\User;

class EmployerApplicationAccess
{
    public static function managesApplication(User $employer, Application $application): bool
    {
        if (! $employer->isEmployer()) {
            return false;
        }

        $application->loadMissing('job');
        $profileId = $employer->employerProfile?->id;
        if ($profileId === null) {
            $employer->loadMissing('employerProfile');
            $profileId = $employer->employerProfile?->id;
        }

        return $application->job !== null
            && $profileId !== null
            && (int) $application->job->employer_id === (int) $profileId;
    }
}
