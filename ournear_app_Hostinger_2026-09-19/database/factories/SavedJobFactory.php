<?php

namespace Database\Factories;

use App\Models\Job;
use App\Models\SavedJob;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SavedJob>
 */
class SavedJobFactory extends Factory
{
    protected $model = SavedJob::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => fn () => User::factory()->candidate()->create()->id,
            'job_id' => fn () => Job::factory()->create()->id,
        ];
    }
}
