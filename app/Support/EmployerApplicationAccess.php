<?php

namespace App\Support;

use App\Models\Application;
use App\Models\User;

class EmployerApplicationAccess
{
    public static function managesApplication(User $employer, Application $application): bool
    {
        $application->loadMissing('job');
        $employer->loadMissing('employerProfile');
        $profileId = $employer->employerProfile?->id;

        return $application->job !== null
            && $profileId !== null
            && (int) $application->job->employer_id === (int) $profileId;
    }
}
