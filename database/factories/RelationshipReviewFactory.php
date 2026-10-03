<?php

namespace Database\Factories;

use App\Enums\RelationshipReviewDirection;
use App\Enums\RelationshipReviewStatus;
use App\Enums\RelationshipReviewVisibility;
use App\Models\CandidateProfile;
use App\Models\Employee;
use App\Models\EmployerProfile;
use App\Models\RelationshipReview;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RelationshipReview>
 */
class RelationshipReviewFactory extends Factory
{
    protected $model = RelationshipReview::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'employee_id' => Employee::query()->value('id') ?? 1,
            'employer_profile_id' => EmployerProfile::query()->value('id') ?? 1,
            'candidate_profile_id' => CandidateProfile::query()->value('id') ?? 1,
            'reviewer_user_id' => User::factory(),
            'direction' => RelationshipReviewDirection::EmployerToWorker,
            'rating' => 5,
            'body' => 'Reliable worker after joining.',
            'visibility' => RelationshipReviewVisibility::Private,
            'status' => RelationshipReviewStatus::Submitted,
            'hidden_by' => null,
            'hidden_at' => null,
        ];
    }
}
