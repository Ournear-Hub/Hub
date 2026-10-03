<?php

namespace App\Services;

use App\Enums\ApplicationStatus;
use App\Enums\FileLifecycleStatus;
use App\Enums\UserActivityType;
use App\Models\Application;
use App\Models\CandidateResume;
use App\Models\Job;
use App\Models\User;
use App\Services\CandidatePrivateFileService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ApplicationSubmissionService
{
    public function __construct(
        private AdminAlertService $alerts,
        private RecruitmentNotificationService $notifications,
        private UserActivityService $activities,
        private ReferralAttributionService $referrals,
    ) {}

    public function submitFromProfile(Job $job, User $candidate): Application
    {
        if (! $candidate->isCandidate()) {
            abort(403);
        }

        $candidate->loadMissing(['candidateProfile.classifiedLocation', 'candidateProfile.classifiedExperienceRange', 'candidateProfile.classifiedQualification']);
        $profile = $candidate->candidateProfile;
        $resume = $this->usableLibraryResume($candidate);

        if (! $resume instanceof CandidateResume) {
            throw ValidationException::withMessages([
                'resume' => 'Upload a resume to apply.',
            ]);
        }

        return $this->submit($job, [
            'full_name' => (string) $candidate->name,
            'mobile' => (string) ($candidate->mobile ?: ($profile?->mobile ?: 'Not provided')),
            'email' => (string) $candidate->email,
            'current_location' => (string) ($candidate->location ?: ($profile?->classifiedLocation?->name ?: ($profile?->location ?: 'Not provided'))),
            'experience' => (string) ($profile?->classifiedExperienceRange?->name ?: ($profile?->experience ?: 'Not provided')),
            'qualification' => (string) ($profile?->classifiedQualification?->name ?: ($profile?->qualification ?: 'Not provided')),
            'candidate_resume_id' => $resume->id,
            'additional_information' => null,
        ], null);
    }

    public function submit(Job $job, array $validated, ?UploadedFile $resume): Application
    {
        if (! $job->acceptsApplications()) {
            throw ValidationException::withMessages([
                'job' => 'This role is not accepting applications.',
            ]);
        }

        $email = Str::lower($validated['email']);
        $candidateId = $this->authenticatedCandidateId();

        $duplicate = Application::query()
            ->where('job_id', $job->id)
            ->where(function ($query) use ($email, $candidateId) {
                $query->whereRaw('LOWER(email) = ?', [$email]);
                if ($candidateId) {
                    $query->orWhere('user_id', $candidateId);
                }
            })
            ->exists();

        if ($duplicate) {
            throw ValidationException::withMessages([
                'job' => 'You have already applied for this role.',
            ]);
        }

        $snapshot = $this->storeSnapshot($resume, $this->selectedLibraryResume($validated['candidate_resume_id'] ?? null));

        $application = DB::transaction(function () use ($job, $validated, $snapshot, $email) {
            $application = Application::query()->create([
                'job_id' => $job->id,
                'user_id' => $this->authenticatedCandidateId(),
                'full_name' => $validated['full_name'],
                'mobile' => $validated['mobile'],
                'email' => $email,
                'current_location' => $validated['current_location'],
                'experience' => $validated['experience'],
                'qualification' => $validated['qualification'],
                'resume_path' => $snapshot,
                'additional_information' => $validated['additional_information'] ?? null,
                'consent' => true,
                'status' => ApplicationStatus::New,
                'internal_notes' => [],
            ]);

            $this->referrals->attributeApplication($application, request());

            return $application;
        });
        $this->alerts->applicationReceived($application);
        $this->notifications->notifyApplicationSubmitted($application);
        RecruitmentAutomationHooks::fire(\App\Enums\AutomationTrigger::ApplicationSubmitted, [
            'application' => $application,
        ]);

        $applicant = $application->user_id
            ? User::query()->find($application->user_id)
            : null;
        if ($applicant instanceof User) {
            $this->activities->record(
                $applicant,
                UserActivityType::AppliedForJob,
                'Applied for a job.',
                $applicant,
                [
                    'job_id' => $job->id,
                    'job_title' => $job->title,
                    'application_id' => $application->id,
                ],
            );
        }

        return $application;
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

    private function storeSnapshot(?UploadedFile $resume, ?CandidateResume $library): string
    {
        if ($resume instanceof UploadedFile) {
            $extension = strtolower($resume->getClientOriginalExtension() ?: $resume->extension() ?: 'pdf');
            if (! in_array($extension, ['pdf', 'doc', 'docx'], true)) {
                throw ValidationException::withMessages([
                    'resume' => 'The resume must be a PDF, DOC, or DOCX file.',
                ]);
            }

            $filename = Str::uuid()->toString().'.'.$extension;
            $stored = $resume->storeAs('', $filename, 'resumes');

            if ($stored === false) {
                throw ValidationException::withMessages([
                    'resume' => 'The resume could not be stored. Please try again.',
                ]);
            }

            return $filename;
        }

        if ($library instanceof CandidateResume) {
            $disk = (string) ($library->storage_disk ?: CandidatePrivateFileService::DISK);
            $path = (string) $library->storage_path;
            if ($path === '' || ! Storage::disk($disk)->exists($path)) {
                throw ValidationException::withMessages([
                    'candidate_resume_id' => 'The selected resume could not be used.',
                ]);
            }

            $extension = strtolower((string) ($library->extension ?: pathinfo($path, PATHINFO_EXTENSION) ?: 'pdf'));
            if (! in_array($extension, ['pdf', 'doc', 'docx'], true)) {
                throw ValidationException::withMessages([
                    'candidate_resume_id' => 'The selected resume must be a PDF, DOC, or DOCX file.',
                ]);
            }

            $filename = Str::uuid()->toString().'.'.$extension;
            $copied = Storage::disk('resumes')->put($filename, Storage::disk($disk)->get($path));

            if ($copied === false) {
                throw ValidationException::withMessages([
                    'candidate_resume_id' => 'The selected resume could not be stored. Please try again.',
                ]);
            }

            return $filename;
        }

        throw ValidationException::withMessages([
            'resume' => 'Please upload a resume or choose one from your library.',
        ]);
    }

    private function selectedLibraryResume(mixed $resumeId): ?CandidateResume
    {
        $id = (int) $resumeId;
        if ($id <= 0) {
            return null;
        }

        $user = Auth::user();
        if (! $user instanceof User || ! $user->isCandidate()) {
            throw ValidationException::withMessages([
                'candidate_resume_id' => 'Sign in as a candidate to use a saved resume.',
            ]);
        }

        $user->loadMissing('candidateProfile');
        $resume = CandidateResume::query()
            ->whereKey($id)
            ->where('candidate_profile_id', $user->candidateProfile?->id)
            ->where('status', FileLifecycleStatus::Active)
            ->first();

        if (! $resume instanceof CandidateResume) {
            throw ValidationException::withMessages([
                'candidate_resume_id' => 'Select one of your saved resumes.',
            ]);
        }

        return $resume;
    }

    private function usableLibraryResume(User $candidate): ?CandidateResume
    {
        $candidate->loadMissing('candidateProfile');
        $profile = $candidate->candidateProfile;
        if ($profile === null) {
            return null;
        }

        return CandidateResume::query()
            ->where('candidate_profile_id', $profile->id)
            ->where('status', FileLifecycleStatus::Active)
            ->orderByDesc('is_primary')
            ->orderByDesc('id')
            ->first();
    }

    private function authenticatedCandidateId(): ?int
    {
        $user = Auth::user();

        return $user instanceof User && $user->isCandidate() ? $user->id : null;
    }
}
