<?php

namespace Database\Factories;

use App\Enums\FreelancerStatus;
use App\Models\Freelancer;
use App\Models\Industry;
use App\Models\Location;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Freelancer>
 */
class FreelancerFactory extends Factory
{
    protected $model = Freelancer::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $location = Location::query()->first() ?? Location::query()->create([
            'name' => 'Manchester',
            'is_active' => true,
            'sort_order' => 1,
        ]);
        $industry = Industry::query()->first() ?? Industry::query()->create([
            'name' => 'Construction',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        return [
            'submission_token' => Str::random(40),
            'name' => fake()->name(),
            'mobile' => '0700000'.fake()->unique()->numerify('####'),
            'email' => fake()->unique()->safeEmail(),
            'current_location_id' => $location->id,
            'primary_skill' => 'Electrician',
            'industry_id' => $industry->id,
            'skills' => ['Electrical', 'Maintenance'],
            'consent' => true,
            'status' => FreelancerStatus::New,
            'resume_disk' => 'freelancer_files',
            'resume_path' => 'resumes/'.Str::uuid().'.pdf',
            'resume_original_name' => 'resume.pdf',
            'resume_mime' => 'application/pdf',
            'resume_size' => 1024,
            'submitted_at' => now(),
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Freelancer $freelancer) {
            if ($freelancer->current_location_id) {
                $freelancer->preferredLocations()->syncWithoutDetaching([$freelancer->current_location_id]);
            }
        });
    }
}
