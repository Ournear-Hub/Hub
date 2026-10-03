<?php

namespace Database\Factories;

use App\Enums\DocumentVerificationStatus;
use App\Enums\EmployerDocumentType;
use App\Enums\FileLifecycleStatus;
use App\Models\EmployerDocument;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<EmployerDocument>
 */
class EmployerDocumentFactory extends Factory
{
    protected $model = EmployerDocument::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $filename = Str::uuid()->toString().'.pdf';

        return [
            'employer_profile_id' => fn () => User::factory()->employer()->create()->employerProfile?->id,
            'document_type' => EmployerDocumentType::CompanyDocument,
            'title' => 'Company document',
            'original_filename' => 'company-document.pdf',
            'storage_disk' => 'employer_files',
            'storage_path' => 'documents/'.$filename,
            'mime_type' => 'application/pdf',
            'extension' => 'pdf',
            'file_size' => 1024,
            'issue_date' => null,
            'expiry_date' => null,
            'status' => FileLifecycleStatus::Active,
            'verification_status' => DocumentVerificationStatus::Unverified,
            'verified_by' => null,
            'verified_at' => null,
            'review_notes' => null,
        ];
    }
}
