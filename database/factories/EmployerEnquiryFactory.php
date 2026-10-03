<?php

namespace Database\Factories;

use App\Enums\EmployerEnquiryStatus;
use App\Models\EmployerEnquiry;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmployerEnquiry>
 */
class EmployerEnquiryFactory extends Factory
{
    protected $model = EmployerEnquiry::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_name' => fake()->company(),
            'contact_person' => fake()->name(),
            'mobile_number' => '07'.fake()->numerify('#########'),
            'email' => fake()->unique()->companyEmail(),
            'industry' => fake()->randomElement([
                'Construction',
                'Facilities',
                'Hospitality',
                'Logistics',
                'Manufacturing',
                'Office and professional',
            ]),
            'project_location' => fake()->city(),
            'workforce_type' => fake()->randomElement(['Permanent', 'Contract', 'Temporary', 'Project teams']),
            'number_of_people' => fake()->numberBetween(1, 40),
            'required_skills' => fake()->sentence(),
            'expected_start_date' => fake()->optional()->dateTimeBetween('+1 week', '+3 months'),
            'training_required' => fake()->boolean(),
            'additional_requirements' => fake()->optional()->paragraph(),
            'status' => EmployerEnquiryStatus::New,
            'internal_notes' => [],
            'user_id' => null,
        ];
    }
}
