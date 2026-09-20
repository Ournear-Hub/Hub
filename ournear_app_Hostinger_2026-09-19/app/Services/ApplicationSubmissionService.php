<?php

namespace App\Services;

use App\Enums\ApplicationStatus;
use App\Models\Application;
use App\Models\Job;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ApplicationSubmissionService
{
    public function submit(Job $job, array $validated, UploadedFile $resume): Application
    {
        if (! $job->acceptsApplications()) {
            throw ValidationException::withMessages([
                'job' => 'This role is not accepting applications.',
            ]);
        }

        $email = Str::lower($validated['email']);

        if (Application::query()->where('job_id', $job->id)->whereRaw('LOWER(email) = ?', [$email])->exists()) {
            throw ValidationException::withMessages([
                'email' => 'An application for this role has already been submitted with this email address.',
            ]);
        }

        $extension = strtolower($resume->getClientOriginalExtension() ?: $resume->extension() ?: 'pdf');
        if (! in_array($extension, ['pdf', 'doc', 'docx'], true)) {
            throw ValidationException::withMessages([
                'resume' => 'The resume must be a PDF, DOC, or DOCX file.',
            ]);
        }

        $filename = Str::uuid()->toString().'.'.$extension;

        return DB::transaction(function () use ($job, $validated, $resume, $filename, $email) {
            $stored = $resume->storeAs('', $filename, 'resumes');

            if ($stored === false) {
                throw ValidationException::withMessages([
                    'resume' => 'The resume could not be stored. Please try again.',
                ]);
            }

            return Application::query()->create([
                'job_id' => $job->id,
                'user_id' => $this->authenticatedCandidateId(),
                'full_name' => $validated['full_name'],
                'mobile' => $validated['mobile'],
                'email' => $email,
                'current_location' => $validated['current_location'],
                'experience' => $validated['experience'],
                'qualification' => $validated['qualification'],
                'resume_path' => $filename,
                'additional_information' => $validated['additional_information'] ?? null,
                'consent' => true,
                'status' => ApplicationStatus::New,
                'internal_notes' => [],
            ]);
        });
    }

    public function replaceResume(Application $application, UploadedFile $resume, User $candidate): Application
    {
        if (! $candidate->isCandidate() || (int) $application->user_id !== (int) $candidate->id) {
            abort(403);
        }

        $extension = strtolower($resume->getClientOriginalExtension() ?: $resume->extension() ?: 'pdf');
        if (! in_array($extension, ['pdf', 'doc', 'docx'], true)) {
            throw ValidationException::withMessages([
                'resume' => 'The resume must be a PDF, DOC, or DOCX file.',
            ]);
        }

        $filename = Str::uuid()->toString().'.'.$extension;
        $previous = $application->getAttributes()['resume_path'] ?? null;

        $stored = $resume->storeAs('', $filename, 'resumes');

        if ($stored === false) {
            throw ValidationException::withMessages([
                'resume' => 'The resume could not be stored. Please try again.',
            ]);
        }

        $application->forceFill(['resume_path' => $filename])->save();

        if (is_string($previous) && $previous !== '' && $previous !== $filename && Storage::disk('resumes')->exists($previous)) {
            Storage::disk('resumes')->delete($previous);
        }

        return $application->fresh() ?? $application;
    }

    private function authenticatedCandidateId(): ?int
    {
        $user = Auth::user();

        return $user instanceof User && $user->isCandidate() ? $user->id : null;
    }
}
