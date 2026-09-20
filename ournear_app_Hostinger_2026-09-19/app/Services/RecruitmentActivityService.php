<?php

namespace App\Services;

use App\Enums\RecruitmentActivityType;
use App\Models\Application;
use App\Models\EmployerEnquiry;
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
    ): RecruitmentActivity {
        return RecruitmentActivity::query()->create([
            'application_id' => $application?->id,
            'employer_enquiry_id' => $enquiry?->id,
            'type' => $type,
            'body' => $body,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'actor_id' => $actor->id,
        ]);
    }
}
