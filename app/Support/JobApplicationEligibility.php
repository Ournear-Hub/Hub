<?php

namespace App\Support;

use App\Models\Job;
use App\Models\User;

final class JobApplicationEligibility
{
    /**
     * True when the candidate is tied to the employer company that owns this job posting.
     */
    public static function candidateOwnsJobCompany(User $user, Job $job): bool
    {
        if (! $user->isCandidate()) {
            return false;
        }

        if ($job->employer_id === null) {
            return false;
        }

        $job->loadMissing('employer');
        if ($job->employer !== null && (int) $job->employer->user_id === (int) $user->id) {
            return true;
        }

        $user->loadMissing('employerProfile');
        $profile = $user->employerProfile;
        if ($profile === null) {
            return false;
        }

        return (int) $job->employer_id === (int) $profile->id;
    }
}
