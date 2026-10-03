<?php

namespace Database\Factories;

use App\Enums\JobPublishingStatus;
use App\Models\Job;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Job>
 */
class JobFactory extends Factory
{
    protected $model = Job::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->unique()->jobTitle(),
            'project' => fake()->company(),
            'industry' => fake()->randomElement([
                'Construction',
                'Facilities',
                'Hospitality',
                'Logistics',
                'Manufacturing',
                'Office and professional',
            ]),
            'category' => fake()->randomElement(['Permanent', 'Contract', 'Temporary']),
            'location' => fake()->city(),
            'employment_type' => fake()->randomElement(['Full-time', 'Part-time', 'Contract', 'Temporary']),
            'experience' => fake()->randomElement(['0-1 years', '1-3 years', '3-5 years', '5+ years']),
            'salary' => fake()->randomElement(['Competitive', '£28,000 - £32,000', '£18.50 per hour']),
            'vacancy' => fake()->numberBetween(1, 8),
            'posted_date' => now()->subDays(fake()->numberBetween(0, 14)),
            'closing_date' => now()->addDays(fake()->numberBetween(7, 45)),
            'featured' => false,
            'urgent' => false,
            'active' => true,
            'publishing_status' => JobPublishingStatus::Published,
            'joining_date' => null,
            'site' => null,
            'accommodation' => null,
            'description' => fake()->paragraphs(3, true),
            'responsibilities' => fake()->paragraphs(2, true),
            'requirements' => fake()->paragraphs(2, true),
            'what_we_offer' => fake()->paragraphs(2, true),
        ];
    }

    public function draft(): static
    {
        return $this->state(fn () => [
            'publishing_status' => JobPublishingStatus::Draft,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => [
            'active' => false,
        ]);
    }

    public function closed(): static
    {
        return $this->state(fn () => [
            'posted_date' => now()->subDays(30),
            'closing_date' => now()->subDay(),
            'publishing_status' => JobPublishingStatus::Published,
            'active' => true,
        ]);
    }

    public function featured(): static
    {
        return $this->state(fn () => [
            'featured' => true,
            'active' => true,
            'publishing_status' => JobPublishingStatus::Published,
            'closing_date' => now()->addDays(21),
        ]);
    }
}
