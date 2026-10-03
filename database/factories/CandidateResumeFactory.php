<?php

namespace Database\Factories;

use App\Enums\FileLifecycleStatus;
use App\Enums\FileVisibility;
use App\Models\CandidateResume;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<CandidateResume>
 */
class CandidateResumeFactory extends Factory
{
    protected $model = CandidateResume::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $filename = Str::uuid()->toString().'.pdf';

        return [
            'candidate_profile_id' => fn () => User::factory()->candidate()->create()->candidateProfile?->id,
            'title' => 'Curriculum Vitae',
            'original_filename' => 'cv.pdf',
            'storage_disk' => 'candidate_files',
            'storage_path' => 'resumes/'.$filename,
            'mime_type' => 'application/pdf',
            'extension' => 'pdf',
            'file_size' => 1024,
            'is_primary' => false,
            'primary_owner_id' => null,
            'visibility' => FileVisibility::Private,
            'status' => FileLifecycleStatus::Active,
        ];
    }
}
