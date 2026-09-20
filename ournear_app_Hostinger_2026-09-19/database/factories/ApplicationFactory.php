<?php

namespace Database\Factories;

use App\Enums\ApplicationStatus;
use App\Models\Application;
use App\Models\Job;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

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
            'experience' => fake()->randomElement(['0-1 years', '1-3 years', '3-5 years', '5+ years']),
            'qualification' => fake()->randomElement(['GCSEs', 'A-Levels', 'NVQ', 'Degree']),
            'resume_path' => Str::uuid().'.pdf',
            'additional_information' => fake()->optional()->sentence(),
            'consent' => true,
            'status' => ApplicationStatus::New,
            'user_id' => null,
        ];
    }
}
