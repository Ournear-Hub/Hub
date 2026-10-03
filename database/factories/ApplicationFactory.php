<?php

namespace Database\Factories;

use App\Enums\ApplicationStatus;
use App\Models\Application;
use App\Models\Job;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Application>
 */
class ApplicationFactory extends Factory
{
    protected $model = Application::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'job_id' => Job::factory(),
            'full_name' => fake()->name(),
            'mobile' => '07'.fake()->numerify('#########'),
            'email' => fake()->unique()->safeEmail(),
            'current_location' => fake()->city(),
            'experience' => fake()->randomElement(['0-1 years', '1-3 years', '3-5 years']),
            'qualification' => 'NVQ Level 2',
            'resume_path' => 'resume.pdf',
            'additional_information' => null,
            'consent' => true,
            'status' => ApplicationStatus::New,
            'internal_notes' => [],
        ];
    }
}
