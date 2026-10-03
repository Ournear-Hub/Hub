<?php

namespace Database\Factories;

use App\Enums\DocumentType;
use App\Enums\DocumentVerificationStatus;
use App\Enums\FileLifecycleStatus;
use App\Enums\FileVisibility;
use App\Models\CandidateDocument;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<CandidateDocument>
 */
class CandidateDocumentFactory extends Factory
{
    protected $model = CandidateDocument::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $filename = Str::uuid()->toString().'.pdf';

        return [
            'candidate_profile_id' => fn () => User::factory()->candidate()->create()->candidateProfile?->id,
            'document_type' => DocumentType::Certificate,
            'title' => 'Safety certificate',
            'original_filename' => 'certificate.pdf',
            'storage_disk' => 'candidate_files',
            'storage_path' => 'documents/'.$filename,
            'mime_type' => 'application/pdf',
            'extension' => 'pdf',
            'file_size' => 1024,
            'issue_date' => null,
            'expiry_date' => null,
            'visibility' => FileVisibility::Private,
            'status' => FileLifecycleStatus::Active,
            'verification_status' => DocumentVerificationStatus::Unverified,
            'verified_by' => null,
            'verified_at' => null,
        ];
    }
}
