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
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use ZipArchive;

class ResumeImportService
{
    private const MAX_FILES = 250;

    public function __construct(
        private CandidateResumeLibraryService $resumes,
        private OurNearHireIdentity $identity,
    ) {}

    /**
     * @param  list<UploadedFile>  $uploads
     */
    public function import(User $actor, array $uploads): ResumeImportBatch
    {
        $batch = ResumeImportBatch::query()->create([
            'uploaded_by' => $actor->id,
            'status' => 'processing',
        ]);

        $files = $this->expand($uploads);
        $processed = 0;

        foreach ($files as $file) {
            if ($processed >= self::MAX_FILES) {
                $this->record($batch, $file['name'], 'failed', 'not_applicable', 'Import stopped at '.self::MAX_FILES.' files.');
                break;
            }
            $processed++;
            $this->process($batch, $file['name'], $file['contents']);
        }

        $items = $batch->items()->get();
        $batch->forceFill([
            'total_files' => $items->count(),
            'successful' => $items->where('status', 'created')->count(),
            'matched' => $items->where('status', 'matched')->count(),
            'needs_review' => $items->where('status', 'needs_review')->count(),
            'failed' => $items->where('status', 'failed')->count(),
            'emails_queued' => $items->where('email_status', 'queued')->count(),
            'status' => 'completed',
        ])->save();

        return $batch->fresh(['items.user']);
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

    private function process(ResumeImportBatch $batch, string $name, string $contents): void
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
        if (ResumeImportItem::query()->where('content_hash', $hash)->exists()) {
            $this->record($batch, $name, 'matched', 'not_applicable', 'This file was already imported.', $hash);

            return;
        }

        $text = $this->text($extension, $contents);
        $email = $this->email($text);
        if ($email === null) {
            $this->record($batch, $name, 'needs_review', 'not_applicable', 'No email could be read from the resume. Nothing was created.', $hash);

            return;
        }

        $existing = User::query()->where('email', $email)->first();
        if ($existing instanceof User) {
            $this->attach($existing, $name, $contents, $extension);
            $this->record($batch, $name, 'matched', 'not_applicable', 'Matched an existing person. Profile fields were not overwritten.', $hash, $existing->id);

            return;
        }

        try {
            $user = User::query()->create([
                'name' => $this->nameFrom($text, $name),
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
            $this->attach($user, $name, $contents, $extension, $profile);
            $emailStatus = $this->queueWelcome($user);
            $this->record($batch, $name, 'created', $emailStatus, null, $hash, $user->id);
        } catch (\Throwable $exception) {
            $this->record($batch, $name, 'failed', 'failed', 'This resume could not be imported.', $hash);
        }
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

    private function text(string $extension, string $contents): string
    {
        if ($extension === 'docx') {
            $temporary = tempnam(sys_get_temp_dir(), 'docx');
            if ($temporary === false) {
                return '';
            }
            file_put_contents($temporary, $contents);
            $zip = new ZipArchive;
            $xml = '';
            if ($zip->open($temporary) === true) {
                $xml = (string) $zip->getFromName('word/document.xml');
                $zip->close();
            }
            @unlink($temporary);

            return trim(html_entity_decode(strip_tags($xml)));
        }

        return $contents;
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
    ): void {
        ResumeImportItem::query()->create([
            'resume_import_batch_id' => $batch->id,
            'user_id' => $userId,
            'original_filename' => Str::limit($name, 180, ''),
            'content_hash' => $hash,
            'status' => $status,
            'email_status' => $emailStatus,
            'message' => $message,
        ]);
    }
}
