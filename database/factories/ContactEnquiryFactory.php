<?php

namespace Database\Factories;

use App\Enums\ContactEnquiryStatus;
use App\Models\ContactEnquiry;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ContactEnquiry>
 */
class ContactEnquiryFactory extends Factory
{
    protected $model = ContactEnquiry::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'full_name' => fake()->name(),
            'mobile_number' => '07'.fake()->numerify('#########'),
            'email' => fake()->optional()->safeEmail(),
            'interested_in' => fake()->randomElement([
                'Recruitment',
                'Training',
                'Workforce solutions',
                'General enquiry',
            ]),
            'message' => fake()->paragraph(),
            'status' => ContactEnquiryStatus::New,
            'internal_notes' => [],
        ];
    }
}
