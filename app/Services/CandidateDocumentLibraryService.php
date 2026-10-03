<?php

namespace App\Services;

use App\Enums\DocumentType;
use App\Enums\DocumentVerificationStatus;
use App\Enums\FileLifecycleStatus;
use App\Enums\FileVisibility;
use App\Enums\UserActivityType;
use App\Models\CandidateDocument;
use App\Models\CandidateProfile;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class CandidateDocumentLibraryService
{
    public function __construct(
        private CandidatePrivateFileService $files,
        private UserActivityService $activities,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function upload(CandidateProfile $profile, UploadedFile $file, array $attributes, ?User $actor = null): CandidateDocument
    {
        $stored = $this->files->store($file, 'documents', $this->files->documentExtensions());

        $document = CandidateDocument::query()->create([
            'candidate_profile_id' => $profile->id,
            'document_type' => DocumentType::from((string) $attributes['document_type']),
            'title' => $this->title($attributes['title'] ?? null, $stored['original_filename']),
            'original_filename' => $stored['original_filename'],
            'storage_disk' => $stored['disk'],
            'storage_path' => $stored['path'],
            'mime_type' => $stored['mime_type'],
            'extension' => $stored['extension'],
            'file_size' => $stored['file_size'],
            'issue_date' => $attributes['issue_date'] ?? null,
            'expiry_date' => $attributes['expiry_date'] ?? null,
            'visibility' => FileVisibility::tryFrom((string) ($attributes['visibility'] ?? '')) ?? FileVisibility::Private,
            'status' => FileLifecycleStatus::Active,
            'verification_status' => DocumentVerificationStatus::Unverified,
            'verified_by' => null,
            'verified_at' => null,
        ]);

        $this->record($profile, $actor, UserActivityType::UploadedDocument, 'Uploaded a candidate document.');

        return $document;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(CandidateDocument $document, array $attributes, ?User $actor = null): CandidateDocument
    {
        $payload = [];

        if (array_key_exists('title', $attributes)) {
            $payload['title'] = $this->title($attributes['title'], $document->original_filename);
        }
        if (array_key_exists('document_type', $attributes) && $attributes['document_type']) {
            $payload['document_type'] = DocumentType::from((string) $attributes['document_type']);
        }
        if (array_key_exists('visibility', $attributes)) {
            $payload['visibility'] = FileVisibility::tryFrom((string) $attributes['visibility']) ?? $document->visibility;
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
        $this->record($document->candidateProfile, $actor, UserActivityType::UpdatedProfile, 'Updated candidate document details.');

        return $document->refresh();
    }

    public function replace(CandidateDocument $document, UploadedFile $file, ?User $actor = null): CandidateDocument
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
        ])->save();

        $this->files->delete($previousPath, is_string($previousDisk) ? $previousDisk : CandidatePrivateFileService::DISK);
        $this->record($document->candidateProfile, $actor, UserActivityType::UploadedDocument, 'Replaced a candidate document.');

        return $document->refresh();
    }

    public function delete(CandidateDocument $document, ?User $actor = null): void
    {
        $profile = $document->candidateProfile;
        $path = $document->storage_path;
        $disk = $document->storage_disk;

        DB::transaction(fn () => $document->delete());

        $this->files->delete($path, is_string($disk) ? $disk : CandidatePrivateFileService::DISK);
        $this->record($profile, $actor, UserActivityType::UpdatedProfile, 'Deleted a candidate document.');
    }

    private function title(?string $title, string $fallback): string
    {
        $clean = trim((string) $title);

        if ($clean !== '') {
            return mb_substr($clean, 0, 120);
        }

        $base = pathinfo($fallback, PATHINFO_FILENAME);

        return mb_substr($base !== '' ? $base : 'Document', 0, 120);
    }

    private function record(?CandidateProfile $profile, ?User $actor, UserActivityType $type, string $body): void
    {
        $user = $profile?->user;

        if (! $user instanceof User) {
            return;
        }

        $this->activities->record($user, $type, $body, $actor, [
            'candidate_profile_id' => $profile->id,
        ]);
    }
}
