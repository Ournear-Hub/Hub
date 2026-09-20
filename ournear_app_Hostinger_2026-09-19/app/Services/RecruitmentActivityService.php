<?php

namespace App\Services;

use App\Enums\RecruitmentActivityType;
use App\Models\Application;
use App\Models\Employee;
use App\Models\EmployerEnquiry;
use App\Models\EmployerProfile;
use App\Models\EmployerSubmission;
use App\Models\Placement;
use App\Models\RecruitmentActivity;
use App\Models\User;

class RecruitmentActivityService
{
    public function record(
        User $actor,
        RecruitmentActivityType $type,
        ?Application $application = null,
        ?EmployerEnquiry $enquiry = null,
        ?string $body = null,
        ?string $fromStatus = null,
        ?string $toStatus = null,
        ?EmployerSubmission $submission = null,
        ?EmployerProfile $employer = null,
        ?Placement $placement = null,
        ?Employee $employee = null,
    ): RecruitmentActivity {
        return RecruitmentActivity::query()->create([
            'application_id' => $application?->id,
            'employer_enquiry_id' => $enquiry?->id,
            'employer_submission_id' => $submission?->id,
            'employer_profile_id' => $employer?->id ?? $submission?->employer_profile_id ?? $placement?->employer_profile_id,
            'placement_id' => $placement?->id ?? $employee?->placement_id,
            'employee_id' => $employee?->id,
            'type' => $type,
            'body' => $body,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'actor_id' => $actor->id,
        ]);
    }
}
