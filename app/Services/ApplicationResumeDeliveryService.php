<?php

namespace App\Services;

use App\Models\Application;
use App\Services\CandidatePrivateFileService;
use App\Support\ApplicationResumeSnapshot;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ApplicationResumeDeliveryService
{
    private const SNAPSHOT_DISK = 'resumes';

    private const LEGACY_DISK = CandidatePrivateFileService::DISK;

    /**
     * Whether the application snapshot file is present on storage (not merely referenced in DB).
     */
    public function snapshotAvailable(Application $application): bool
    {
        return $this->resolveSnapshot($application) instanceof ApplicationResumeSnapshot;
    }

    public function resolveSnapshot(Application $application): ?ApplicationResumeSnapshot
    {
        $path = $application->resumeStoragePath();
        $sanitized = $this->sanitizeRelativePath($path);
        if ($sanitized === null) {
            return null;
        }

        if (Storage::disk(self::SNAPSHOT_DISK)->exists($sanitized)) {
            return new ApplicationResumeSnapshot(
                self::SNAPSHOT_DISK,
                $sanitized,
                $this->extensionFromPath($sanitized),
            );
        }

        // Historical rows may reference a library-relative path that exists only on candidate_files.
        if (Storage::disk(self::LEGACY_DISK)->exists($sanitized)) {
            return new ApplicationResumeSnapshot(
                self::LEGACY_DISK,
                $sanitized,
                $this->extensionFromPath($sanitized),
            );
        }

        return null;
    }

    public function stream(
        Request $request,
        Application $application,
        string $downloadLabel,
    ): StreamedResponse {
        $snapshot = $this->resolveSnapshot($application);
        abort_unless($snapshot instanceof ApplicationResumeSnapshot, 404);

        $wantsInline = $request->string('disposition')->toString() === 'inline';
        $inline = $wantsInline && $snapshot->isPdf();
        $downloadName = $this->safeDownloadFilename($downloadLabel, $snapshot->extension);
        $mime = $this->mimeForExtension($snapshot->extension);

        return Storage::disk($snapshot->disk)->response(
            $snapshot->path,
            $downloadName,
            [
                'Content-Type' => $mime,
                'Content-Disposition' => sprintf(
                    '%s; filename="%s"',
                    $inline ? 'inline' : 'attachment',
                    str_replace('"', '', $downloadName)
                ),
                'Cache-Control' => 'private, no-store, max-age=0',
                'X-Content-Type-Options' => 'nosniff',
            ],
            $inline ? 'inline' : 'attachment',
        );
    }

    private function sanitizeRelativePath(?string $path): ?string
    {
        if (! is_string($path)) {
            return null;
        }

        $path = str_replace('\\', '/', trim($path));
        if ($path === '' || str_starts_with($path, '/') || str_contains($path, '..')) {
            return null;
        }

        return $path;
    }

    private function extensionFromPath(string $path): string
    {
        return strtolower(pathinfo($path, PATHINFO_EXTENSION) ?: 'bin');
    }

    private function safeDownloadFilename(string $label, string $extension): string
    {
        $label = preg_replace('/[^A-Za-z0-9._-]+/', '-', trim($label)) ?: 'application';
        $label = trim($label, '-');

        return $extension !== '' && $extension !== 'bin'
            ? $label.'.'.$extension
            : $label;
    }

    private function mimeForExtension(string $extension): string
    {
        return match ($extension) {
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            default => 'application/octet-stream',
        };
    }
}
