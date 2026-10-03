<?php

namespace App\Services;

use App\Enums\InternalCandidateResumeSource;
use App\Models\InternalCandidateResume;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InternalCandidateResumeService
{
    private const STORAGE_DIRECTORY = 'internal-resumes';

    public function __construct(
        private CandidatePrivateFileService $files,
    ) {}

    /**
     * @param  array{display_name: string, source: InternalCandidateResumeSource|string, notes?: string|null, user_id?: int|null}  $attributes
     */
    public function store(User $actor, UploadedFile $file, array $attributes): InternalCandidateResume
    {
        Gate::forUser($actor)->authorize('create', InternalCandidateResume::class);

        $source = $this->source($attributes['source'] ?? null);
        $displayName = $this->displayName($attributes['display_name'] ?? null);
        $linkedUserId = $this->optionalUserId($attributes['user_id'] ?? null);
        $notes = $this->notes($attributes['notes'] ?? null);

        $stored = $this->files->store($file, self::STORAGE_DIRECTORY, $this->files->resumeExtensions());

        return InternalCandidateResume::query()->create([
            'display_name' => $displayName,
            'source' => $source,
            'notes' => $notes,
            'original_filename' => $stored['original_filename'],
            'storage_disk' => $stored['disk'],
            'storage_path' => $stored['path'],
            'mime_type' => $stored['mime_type'],
            'extension' => $stored['extension'],
            'file_size' => $stored['file_size'],
            'user_id' => $linkedUserId,
            'created_by' => $actor->id,
        ]);
    }

    public function replace(User $actor, InternalCandidateResume $resume, UploadedFile $file): InternalCandidateResume
    {
        Gate::forUser($actor)->authorize('update', $resume);

        $previousDisk = $resume->storage_disk;
        $previousPath = $resume->storage_path;
        $stored = $this->files->store($file, self::STORAGE_DIRECTORY, $this->files->resumeExtensions());

        $resume->forceFill([
            'original_filename' => $stored['original_filename'],
            'storage_disk' => $stored['disk'],
            'storage_path' => $stored['path'],
            'mime_type' => $stored['mime_type'],
            'extension' => $stored['extension'],
            'file_size' => $stored['file_size'],
        ])->save();

        $this->files->delete($previousPath, is_string($previousDisk) ? $previousDisk : CandidatePrivateFileService::DISK);

        return $resume->refresh();
    }

    public function download(User $actor, InternalCandidateResume $resume, bool $inline = false): StreamedResponse
    {
        Gate::forUser($actor)->authorize('download', $resume);

        return $this->files->download(
            $resume->storage_path,
            $resume->original_filename,
            $inline,
            is_string($resume->storage_disk) ? $resume->storage_disk : CandidatePrivateFileService::DISK,
        );
    }

    public function delete(User $actor, InternalCandidateResume $resume): void
    {
        Gate::forUser($actor)->authorize('delete', $resume);

        $path = $resume->storage_path;
        $disk = $resume->storage_disk;

        DB::transaction(function () use ($resume): void {
            $resume->delete();
        });

        $this->files->delete($path, is_string($disk) ? $disk : CandidatePrivateFileService::DISK);
    }

    /**
     * Explicit optional link to an existing portal user. Does not create users or profiles.
     */
    public function linkUser(User $actor, InternalCandidateResume $resume, ?User $user): InternalCandidateResume
    {
        Gate::forUser($actor)->authorize('update', $resume);

        $resume->forceFill([
            'user_id' => $user?->id,
        ])->save();

        return $resume->refresh();
    }

    private function source(mixed $value): InternalCandidateResumeSource
    {
        if ($value instanceof InternalCandidateResumeSource) {
            return $value;
        }

        $source = InternalCandidateResumeSource::tryFrom((string) $value);
        if ($source instanceof InternalCandidateResumeSource) {
            return $source;
        }

        throw ValidationException::withMessages([
            'source' => 'The resume source is invalid.',
        ]);
    }

    private function displayName(mixed $value): string
    {
        $name = trim((string) $value);
        if ($name === '') {
            throw ValidationException::withMessages([
                'display_name' => 'A candidate name is required.',
            ]);
        }

        return mb_substr($name, 0, 180);
    }

    private function notes(mixed $value): ?string
    {
        $notes = trim((string) $value);

        return $notes === '' ? null : mb_substr($notes, 0, 2000);
    }

    private function optionalUserId(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        $userId = (int) $value;
        if ($userId <= 0) {
            throw ValidationException::withMessages([
                'user_id' => 'The linked user is invalid.',
            ]);
        }

        if (! User::query()->whereKey($userId)->exists()) {
            throw ValidationException::withMessages([
                'user_id' => 'The selected user does not exist.',
            ]);
        }

        return $userId;
    }
}
