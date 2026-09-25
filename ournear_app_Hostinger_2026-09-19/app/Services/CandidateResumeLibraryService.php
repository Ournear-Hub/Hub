<?php

namespace App\Services;

use App\Enums\FileLifecycleStatus;
use App\Enums\FileVisibility;
use App\Enums\UserActivityType;
use App\Models\Application;
use App\Models\CandidateProfile;
use App\Models\CandidateResume;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CandidateResumeLibraryService
{
    public function __construct(
        private CandidatePrivateFileService $files,
        private UserActivityService $activities,
    ) {}

    /**
     * @param  array{title?: string|null, visibility?: string|null, is_primary?: bool}  $attributes
     */
    public function upload(CandidateProfile $profile, UploadedFile $file, array $attributes, ?User $actor = null): CandidateResume
    {
        $stored = $this->files->store($file, 'resumes', $this->files->resumeExtensions());
        $makePrimary = ($attributes['is_primary'] ?? false) === true
            || ! $profile->resumes()->where('is_primary', true)->exists();

        $resume = DB::transaction(function () use ($profile, $stored, $attributes, $makePrimary) {
            $resume = CandidateResume::query()->create([
                'candidate_profile_id' => $profile->id,
                'title' => $this->title($attributes['title'] ?? null, $stored['original_filename']),
                'original_filename' => $stored['original_filename'],
                'storage_disk' => $stored['disk'],
                'storage_path' => $stored['path'],
                'mime_type' => $stored['mime_type'],
                'extension' => $stored['extension'],
                'file_size' => $stored['file_size'],
                'is_primary' => false,
                'primary_owner_id' => null,
                'visibility' => FileVisibility::tryFrom((string) ($attributes['visibility'] ?? '')) ?? FileVisibility::Private,
                'status' => FileLifecycleStatus::Active,
            ]);

            if ($makePrimary) {
                $this->markPrimary($profile, $resume);
            }

            return $resume->refresh();
        });

        $this->record($profile, $actor, UserActivityType::UploadedResume, 'Uploaded a resume to the candidate library.');

        return $resume;
    }

    /**
     * @param  array{title?: string|null, visibility?: string|null}  $attributes
     */
    public function update(CandidateResume $resume, array $attributes, ?User $actor = null): CandidateResume
    {
        $resume->fill([
            'title' => array_key_exists('title', $attributes)
                ? $this->title($attributes['title'], $resume->original_filename)
                : $resume->title,
            'visibility' => array_key_exists('visibility', $attributes)
                ? (FileVisibility::tryFrom((string) $attributes['visibility']) ?? $resume->visibility)
                : $resume->visibility,
        ])->save();

        $this->record($resume->candidateProfile, $actor, UserActivityType::UpdatedProfile, 'Updated resume library details.');

        return $resume->refresh();
    }

    public function replace(CandidateResume $resume, UploadedFile $file, ?User $actor = null): CandidateResume
    {
        $previousDisk = $resume->storage_disk;
        $previousPath = $resume->storage_path;
        $stored = $this->files->store($file, 'resumes', $this->files->resumeExtensions());

        $resume->forceFill([
            'original_filename' => $stored['original_filename'],
            'storage_disk' => $stored['disk'],
            'storage_path' => $stored['path'],
            'mime_type' => $stored['mime_type'],
            'extension' => $stored['extension'],
            'file_size' => $stored['file_size'],
            'status' => FileLifecycleStatus::Active,
        ])->save();

        $this->files->delete($previousPath, is_string($previousDisk) ? $previousDisk : CandidatePrivateFileService::DISK);
        $this->assertIndependentFromApplications($previousPath);
        $this->record($resume->candidateProfile, $actor, UserActivityType::UploadedResume, 'Replaced a candidate library resume.');

        return $resume->refresh();
    }

    public function makePrimary(CandidateResume $resume, ?User $actor = null): CandidateResume
    {
        $profile = $resume->candidateProfile;
        abort_unless($profile instanceof CandidateProfile, 404);

        DB::transaction(function () use ($profile, $resume) {
            $resume->forceFill(['status' => FileLifecycleStatus::Active])->save();
            $this->markPrimary($profile, $resume);
        });

        $this->record($profile, $actor, UserActivityType::UpdatedProfile, 'Set the primary resume.');

        return $resume->refresh();
    }

    public function archive(CandidateResume $resume, ?User $actor = null): CandidateResume
    {
        $profile = $resume->candidateProfile;
        abort_unless($profile instanceof CandidateProfile, 404);

        DB::transaction(function () use ($profile, $resume) {
            $wasPrimary = $resume->is_primary;
            $resume->forceFill([
                'status' => FileLifecycleStatus::Archived,
                'is_primary' => false,
                'primary_owner_id' => null,
            ])->save();

            if ($wasPrimary) {
                $this->promoteNextPrimary($profile);
            }
        });

        $this->record($profile, $actor, UserActivityType::UpdatedProfile, 'Archived a candidate library resume.');

        return $resume->refresh();
    }

    public function delete(CandidateResume $resume, ?User $actor = null): void
    {
        $profile = $resume->candidateProfile;
        abort_unless($profile instanceof CandidateProfile, 404);

        $path = $resume->storage_path;
        $disk = $resume->storage_disk;
        $wasPrimary = $resume->is_primary;

        $this->assertIndependentFromApplications($path);

        DB::transaction(function () use ($resume, $profile, $wasPrimary) {
            $resume->delete();

            if ($wasPrimary) {
                $this->promoteNextPrimary($profile);
            }
        });

        $this->files->delete($path, is_string($disk) ? $disk : CandidatePrivateFileService::DISK);
        $this->record($profile, $actor, UserActivityType::UpdatedProfile, 'Deleted a candidate library resume.');
    }

    private function markPrimary(CandidateProfile $profile, CandidateResume $resume): void
    {
        CandidateResume::query()
            ->where('candidate_profile_id', $profile->id)
            ->update([
                'is_primary' => false,
                'primary_owner_id' => null,
            ]);

        $resume->forceFill([
            'is_primary' => true,
            'primary_owner_id' => $profile->id,
            'status' => FileLifecycleStatus::Active,
        ])->save();
    }

    private function promoteNextPrimary(CandidateProfile $profile): void
    {
        $next = CandidateResume::query()
            ->where('candidate_profile_id', $profile->id)
            ->where('status', FileLifecycleStatus::Active->value)
            ->latest()
            ->first();

        if ($next instanceof CandidateResume) {
            $this->markPrimary($profile, $next);
        }
    }

    private function title(?string $title, string $fallback): string
    {
        $clean = trim((string) $title);

        if ($clean !== '') {
            return mb_substr($clean, 0, 120);
        }

        $base = pathinfo($fallback, PATHINFO_FILENAME);

        return mb_substr($base !== '' ? $base : 'Resume', 0, 120);
    }

    private function assertIndependentFromApplications(?string $path): void
    {
        if (! is_string($path) || $path === '') {
            return;
        }

        if (Application::query()->where('resume_path', $path)->exists()) {
            throw ValidationException::withMessages([
                'file' => 'This file is an application resume snapshot and cannot be changed from the library.',
            ]);
        }
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
