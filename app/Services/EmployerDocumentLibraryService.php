<?php

namespace App\Services;

use App\Enums\DocumentVerificationStatus;
use App\Enums\EmployerDocumentType;
use App\Enums\FileLifecycleStatus;
use App\Enums\UserActivityType;
use App\Models\EmployerDocument;
use App\Models\EmployerProfile;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class EmployerDocumentLibraryService
{
    public function __construct(
        private EmployerPrivateFileService $files,
        private UserActivityService $activities,
    ) {}

    /**
     * @return list<EmployerDocument>
     */
    public function list(EmployerProfile $profile, ?EmployerDocumentType $type = null): array
    {
        return $profile->documents()
            ->when($type, fn ($query) => $query->where('document_type', $type->value))
            ->get()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function upload(EmployerProfile $profile, UploadedFile $file, array $attributes, ?User $actor = null): EmployerDocument
    {
        $stored = $this->files->store($file, 'documents', $this->files->documentExtensions());

        $document = EmployerDocument::query()->create([
            'employer_profile_id' => $profile->id,
            'document_type' => EmployerDocumentType::from((string) $attributes['document_type']),
            'title' => $this->title($attributes['title'] ?? null, $stored['original_filename']),
            'original_filename' => $stored['original_filename'],
            'storage_disk' => $stored['disk'],
            'storage_path' => $stored['path'],
            'mime_type' => $stored['mime_type'],
            'extension' => $stored['extension'],
            'file_size' => $stored['file_size'],
            'issue_date' => $attributes['issue_date'] ?? null,
            'expiry_date' => $attributes['expiry_date'] ?? null,
            'status' => FileLifecycleStatus::Active,
            'verification_status' => DocumentVerificationStatus::Unverified,
            'verified_by' => null,
            'verified_at' => null,
            'review_notes' => null,
        ]);

        $this->record($profile, $actor, UserActivityType::UploadedDocument, 'Uploaded a company document.', [
            'employer_document_id' => $document->id,
        ]);

        return $document;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(EmployerDocument $document, array $attributes, ?User $actor = null): EmployerDocument
    {
        $payload = [];

        if (array_key_exists('title', $attributes)) {
            $payload['title'] = $this->title($attributes['title'], $document->original_filename);
        }
        if (array_key_exists('document_type', $attributes) && $attributes['document_type']) {
            $payload['document_type'] = EmployerDocumentType::from((string) $attributes['document_type']);
        }
        if (array_key_exists('issue_date', $attributes)) {
            $payload['issue_date'] = $attributes['issue_date'] ?: null;
        }
        if (array_key_exists('expiry_date', $attributes)) {
            $payload['expiry_date'] = $attributes['expiry_date'] ?: null;
        }
        if (array_key_exists('status', $attributes) && FileLifecycleStatus::tryFrom((string) $attributes['status'])) {
            $payload['status'] = FileLifecycleStatus::from((string) $attributes['status']);
        }

        $document->fill($payload)->save();
        $this->record($document->employerProfile, $actor, UserActivityType::UpdatedProfile, 'Updated company document details.', [
            'employer_document_id' => $document->id,
        ]);

        return $document->refresh();
    }

    public function replace(EmployerDocument $document, UploadedFile $file, ?User $actor = null): EmployerDocument
    {
        $previousDisk = $document->storage_disk;
        $previousPath = $document->storage_path;
        $stored = $this->files->store($file, 'documents', $this->files->documentExtensions());

        $document->forceFill([
            'original_filename' => $stored['original_filename'],
            'storage_disk' => $stored['disk'],
            'storage_path' => $stored['path'],
            'mime_type' => $stored['mime_type'],
            'extension' => $stored['extension'],
            'file_size' => $stored['file_size'],
            'verification_status' => DocumentVerificationStatus::Unverified,
            'verified_by' => null,
            'verified_at' => null,
            'review_notes' => null,
        ])->save();

        $this->files->delete($previousPath, is_string($previousDisk) ? $previousDisk : EmployerPrivateFileService::DISK);
        $this->record($document->employerProfile, $actor, UserActivityType::UploadedDocument, 'Replaced a company document.', [
            'employer_document_id' => $document->id,
        ]);

        return $document->refresh();
    }

    public function delete(EmployerDocument $document, ?User $actor = null): void
    {
        $profile = $document->employerProfile;
        $path = $document->storage_path;
        $disk = $document->storage_disk;
        $documentId = $document->id;

        DB::transaction(fn () => $document->delete());

        $this->files->delete($path, is_string($disk) ? $disk : EmployerPrivateFileService::DISK);
        $this->record($profile, $actor, UserActivityType::UpdatedProfile, 'Deleted a company document.', [
            'employer_document_id' => $documentId,
        ]);
    }

    public function review(
        EmployerDocument $document,
        string $action,
        User $actor,
        ?string $notes = null,
    ): EmployerDocument {
        $status = match ($action) {
            'verify' => DocumentVerificationStatus::Verified,
            'reject' => DocumentVerificationStatus::Rejected,
            default => throw new InvalidArgumentException('Unsupported company document review action.'),
        };

        $cleanNotes = is_string($notes) ? trim($notes) : '';

        $document->forceFill([
            'verification_status' => $status,
            'verified_by' => $actor->id,
            'verified_at' => now(),
            'review_notes' => $cleanNotes !== '' ? mb_substr($cleanNotes, 0, 2000) : null,
        ])->save();

        $this->record(
            $document->employerProfile,
            $actor,
            UserActivityType::UpdatedProfile,
            $status === DocumentVerificationStatus::Verified
                ? 'Company document verified.'
                : 'Company document rejected.',
            ['employer_document_id' => $document->id],
        );

        return $document->refresh();
    }

    private function title(?string $title, string $fallback): string
    {
        $clean = trim((string) $title);

        if ($clean !== '') {
            return mb_substr($clean, 0, 120);
        }

        $base = pathinfo($fallback, PATHINFO_FILENAME);

        return mb_substr($base !== '' ? $base : 'Company document', 0, 120);
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    private function record(?EmployerProfile $profile, ?User $actor, UserActivityType $type, string $body, array $metadata = []): void
    {
        $user = $profile?->user ?? $profile?->user()->first();

        if (! $user instanceof User) {
            return;
        }

        $this->activities->record($user, $type, $body, $actor, array_filter([
            'employer_profile_id' => $profile->id,
            ...$metadata,
        ]));
    }
}
