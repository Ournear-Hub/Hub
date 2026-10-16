<?php

namespace App\Services;

use App\Enums\FileVisibility;
use App\Enums\RegistrationSource;
use App\Enums\UserRole;
use App\Mail\ImportedCandidateWelcomeMail;
use App\Models\CandidateProfile;
use App\Models\ResumeImportBatch;
use App\Models\ResumeImportItem;
use App\Models\User;
use App\Support\OurNearHireIdentity;
use App\Support\ResumeTextExtractor;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use ZipArchive;

class ResumeImportService
{
    private const MAX_FILES = 250;

    public function __construct(
        private CandidateResumeLibraryService $resumes,
        private OurNearHireIdentity $identity,
        private ResumeTextExtractor $textExtractor,
    ) {}

    /**
     * @param  list<UploadedFile>  $uploads
     */
    public function import(User $actor, array $uploads): ResumeImportBatch
    {
        return $this->stage($actor, $uploads);
    }

    /**
     * @param  list<UploadedFile>  $uploads
     */
    public function stage(User $actor, array $uploads): ResumeImportBatch
    {
        $batch = ResumeImportBatch::query()->create([
            'uploaded_by' => $actor->id,
            'status' => 'awaiting_confirmation',
        ]);

        $files = $this->expand($uploads);
        $processed = 0;

        foreach ($files as $file) {
            if ($processed >= self::MAX_FILES) {
                $this->record($batch, $file['name'], 'failed', 'not_applicable', 'Import stopped at '.self::MAX_FILES.' files.');

                break;
            }
            $processed++;
            $this->stageFile($batch, $file['name'], $file['contents']);
        }

        return $this->refreshBatchCounts($batch->fresh(['items.user']) ?? $batch);
    }

    public function confirm(User $actor, ResumeImportBatch $batch): ResumeImportBatch
    {
        if ($batch->status !== 'awaiting_confirmation') {
            throw ValidationException::withMessages([
                'batch' => 'This import batch has already been processed.',
            ]);
        }

        if ((int) $batch->uploaded_by !== (int) $actor->id && ! $actor->hasPermission('candidates.manage')) {
            throw ValidationException::withMessages([
                'batch' => 'You cannot confirm this import batch.',
            ]);
        }

        foreach ($batch->items()->whereIn('status', ['ready', 'matched'])->cursor() as $item) {
            $this->confirmItem($item);
        }

        $batch->status = 'completed';
        $batch->load(['items.user']);

        return $this->refreshBatchCounts($batch);
    }

    private function confirmItem(ResumeImportItem $item): void
    {
        $contents = $this->readStagedContents($item);
        if ($contents === null) {
            $item->update([
                'status' => 'failed',
                'message' => 'Staged file could not be read.',
            ]);

            return;
        }

        $extension = (string) ($item->file_extension ?? pathinfo((string) $item->original_filename, PATHINFO_EXTENSION));

        if ($item->status === 'matched' && $item->user_id !== null) {
            $user = User::query()->find($item->user_id);
            if ($user instanceof User) {
                $this->attach($user, (string) $item->original_filename, $contents, $extension);
                $item->update(['message' => 'Resume attached to matched person. Profile fields were not overwritten.']);
            }
            $this->deleteStagedFile($item);

            return;
        }

        if ($item->status !== 'ready') {
            return;
        }

        $email = strtolower(trim((string) $item->extracted_email));
        if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $item->update([
                'status' => 'needs_review',
                'message' => 'No valid email was available to create a person.',
            ]);
            $this->deleteStagedFile($item);

            return;
        }

        $existing = User::query()->where('email', $email)->first();
        if ($existing instanceof User) {
            $this->attach($existing, (string) $item->original_filename, $contents, $extension);
            $item->update([
                'status' => 'matched',
                'user_id' => $existing->id,
                'message' => 'Matched an existing person during confirmation. Profile fields were not overwritten.',
            ]);
            $this->deleteStagedFile($item);

            return;
        }

        try {
            $user = User::query()->create([
                'name' => (string) ($item->extracted_name ?: 'Imported candidate'),
                'email' => $email,
                'role' => UserRole::Candidate,
                'registration_source' => RegistrationSource::Admin,
                'password' => Hash::make(Str::random(40)),
                'is_active' => true,
            ]);
            $user->forceFill([
                'must_change_password' => true,
                'email_verified_at' => null,
            ])->save();
            $profile = CandidateProfile::query()->create(['user_id' => $user->id]);
            $this->attach($user, (string) $item->original_filename, $contents, $extension, $profile);
            $emailStatus = $this->queueWelcome($user);
            $item->update([
                'status' => 'created',
                'user_id' => $user->id,
                'email_status' => $emailStatus,
                'message' => null,
            ]);
        } catch (\Throwable) {
            $item->update([
                'status' => 'failed',
                'email_status' => 'failed',
                'message' => 'This resume could not be imported.',
            ]);
        }

        $this->deleteStagedFile($item);
    }

    /**
     * @param  list<UploadedFile>  $uploads
     * @return list<array{name: string, contents: string}>
     */
    private function expand(array $uploads): array
    {
        $files = [];
        foreach ($uploads as $upload) {
            $extension = strtolower($upload->getClientOriginalExtension());
            if ($extension === 'zip') {
                $files = array_merge($files, $this->unzip($upload));

                continue;
            }
            $files[] = [
                'name' => basename($upload->getClientOriginalName()),
                'contents' => (string) file_get_contents($upload->getRealPath()),
            ];
        }

        return $files;
    }

    /**
     * @return list<array{name: string, contents: string}>
     */
    private function unzip(UploadedFile $upload): array
    {
        $zip = new ZipArchive;
        if ($zip->open($upload->getRealPath()) !== true) {
            return [['name' => basename($upload->getClientOriginalName()), 'contents' => '']];
        }

        $files = [];
        for ($index = 0; $index < $zip->numFiles; $index++) {
            $name = (string) $zip->getNameIndex($index);
            if (str_contains($name, '..') || str_ends_with($name, '/')) {
                continue;
            }
            $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            if (! in_array($extension, ['pdf', 'doc', 'docx'], true)) {
                continue;
            }
            $contents = $zip->getFromIndex($index);
            if (! is_string($contents)) {
                continue;
            }
            $files[] = ['name' => basename($name), 'contents' => $contents];
        }
        $zip->close();

        return $files;
    }

    private function stageFile(ResumeImportBatch $batch, string $name, string $contents): void
    {
        $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        if (! in_array($extension, ['pdf', 'doc', 'docx'], true) || str_contains($contents, '<?php')) {
            $this->record($batch, $name, 'failed', 'not_applicable', 'File type is not allowed.');

            return;
        }

        if (strlen($contents) > 8 * 1024 * 1024) {
            $this->record($batch, $name, 'failed', 'not_applicable', 'File is larger than 8 MB.');

            return;
        }

        $hash = hash('sha256', $contents);
        if (ResumeImportItem::query()->where('content_hash', $hash)->where('status', '!=', 'failed')->exists()) {
            $this->record($batch, $name, 'matched', 'not_applicable', 'This file was already imported.', $hash);

            return;
        }

        $text = $this->textExtractor->extract($extension, $contents);
        $email = $this->email($text);
        $extractedName = $this->nameFrom($text, $name);

        if ($email === null) {
            $this->record(
                $batch,
                $name,
                'needs_review',
                'not_applicable',
                'No email could be read from the resume. Nothing will be created until details are corrected.',
                $hash,
                null,
                $extractedName,
                null,
                $extension,
            );

            return;
        }

        $existing = User::query()->where('email', $email)->first();
        $storagePath = $this->storeStagedFile($batch, $hash, $extension, $contents);

        if ($existing instanceof User) {
            $this->record(
                $batch,
                $name,
                'matched',
                'not_applicable',
                'Matched an existing person. Confirm to attach the resume without overwriting profile fields.',
                $hash,
                $existing->id,
                $extractedName,
                $email,
                $extension,
                $storagePath,
            );

            return;
        }

        $this->record(
            $batch,
            $name,
            'ready',
            'not_applicable',
            'Ready to import. Confirm to create the candidate and attach this resume.',
            $hash,
            null,
            $extractedName,
            $email,
            $extension,
            $storagePath,
        );
    }

    private function storeStagedFile(ResumeImportBatch $batch, string $hash, string $extension, string $contents): string
    {
        $path = 'resume-imports/'.$batch->id.'/'.$hash.'.'.$extension;
        Storage::disk('local')->put($path, $contents);

        return $path;
    }

    private function readStagedContents(ResumeImportItem $item): ?string
    {
        $path = (string) ($item->storage_path ?? '');
        if ($path === '' || ! Storage::disk('local')->exists($path)) {
            return null;
        }

        return Storage::disk('local')->get($path);
    }

    private function deleteStagedFile(ResumeImportItem $item): void
    {
        $path = (string) ($item->storage_path ?? '');
        if ($path !== '' && Storage::disk('local')->exists($path)) {
            Storage::disk('local')->delete($path);
        }
        $item->update(['storage_path' => null]);
    }

    private function attach(User $user, string $name, string $contents, string $extension, ?CandidateProfile $profile = null): void
    {
        $profile ??= $user->candidateProfile ?? CandidateProfile::query()->create(['user_id' => $user->id]);
        $temporary = tempnam(sys_get_temp_dir(), 'resume');
        if ($temporary === false) {
            return;
        }
        $path = $temporary.'.'.$extension;
        rename($temporary, $path);
        file_put_contents($path, $contents);
        $upload = new UploadedFile($path, $name, null, null, true);
        $this->resumes->upload($profile, $upload, [
            'title' => pathinfo($name, PATHINFO_FILENAME),
            'visibility' => FileVisibility::Private->value,
            'is_primary' => ! $profile->resumes()->where('is_primary', true)->exists(),
        ], $user);
        @unlink($path);
    }

    private function queueWelcome(User $user): string
    {
        try {
            $token = Password::broker()->createToken($user);
            $url = url(route('password.reset', [
                'token' => $token,
                'email' => $user->email,
            ], false));
            Mail::to($user->email)->queue(new ImportedCandidateWelcomeMail(
                $user,
                $this->identity->display($user, 'CAN'),
                $url,
            ));

            return 'queued';
        } catch (\Throwable) {
            return 'failed';
        }
    }

    private function email(string $text): ?string
    {
        if (! preg_match('/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/i', $text, $match)) {
            return null;
        }

        $email = strtolower($match[0]);

        return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;
    }

    private function nameFrom(string $text, string $filename): string
    {
        $line = trim((string) strtok($text, "\n"));
        $line = trim(preg_replace('/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/i', '', $line) ?? '');
        if ($line !== '' && ! str_contains(strtolower($line), 'pdf')) {
            return Str::limit($line, 120, '');
        }

        $base = pathinfo($filename, PATHINFO_FILENAME);

        return $base !== '' ? Str::limit(str_replace(['_', '-'], ' ', $base), 120, '') : 'Imported candidate';
    }

    private function record(
        ResumeImportBatch $batch,
        string $name,
        string $status,
        string $emailStatus,
        ?string $message,
        ?string $hash = null,
        ?int $userId = null,
        ?string $extractedName = null,
        ?string $extractedEmail = null,
        ?string $extension = null,
        ?string $storagePath = null,
    ): void {
        ResumeImportItem::query()->create([
            'resume_import_batch_id' => $batch->id,
            'user_id' => $userId,
            'original_filename' => Str::limit($name, 180, ''),
            'extracted_name' => $extractedName,
            'extracted_email' => $extractedEmail,
            'storage_path' => $storagePath,
            'file_extension' => $extension,
            'content_hash' => $hash,
            'status' => $status,
            'email_status' => $emailStatus,
            'message' => $message,
        ]);
    }

    private function refreshBatchCounts(ResumeImportBatch $batch): ResumeImportBatch
    {
        $items = $batch->items()->get();
        $attributes = [
            'total_files' => $items->count(),
            'successful' => $items->where('status', 'created')->count(),
            'matched' => $items->where('status', 'matched')->count(),
            'needs_review' => $items->where('status', 'needs_review')->count(),
            'failed' => $items->where('status', 'failed')->count(),
            'emails_queued' => $items->where('email_status', 'queued')->count(),
        ];
        if ($batch->status !== null && $batch->status !== '') {
            $attributes['status'] = $batch->status;
        }
        $batch->forceFill($attributes)->save();

        return $batch->fresh(['items.user']) ?? $batch;
    }
}
