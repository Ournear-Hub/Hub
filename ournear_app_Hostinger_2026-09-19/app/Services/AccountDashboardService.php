<?php

namespace App\Services;

use App\Enums\DocumentType;
use App\Enums\DocumentVerificationStatus;
use App\Models\Application;
use App\Models\CandidateDocument;
use App\Models\CandidateResume;
use App\Models\SavedJob;
use App\Models\User;

class AccountDashboardService
{
    public function __construct(private EmployerPostingEligibilityService $employerEligibility) {}

    /**
     * @return array{role: string, profile: array<string, mixed>, dashboard: ?array<string, mixed>}
     */
    public function home(User $user): array
    {
        $user->loadMissing(['candidateProfile', 'employerProfile']);

        return [
            'role' => $user->role->value,
            'profile' => $user->toAccountProfileArray(),
            'dashboard' => $user->isCandidate()
                ? $this->candidateDashboard($user)
                : ($user->isEmployer() ? $this->employerDashboard($user) : null),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function employerDashboard(User $user): array
    {
        $profile = $user->employerProfile;
        $jobs = $profile?->jobs();

        return [
            'company_name' => $profile?->displayCompany() ?? 'Company profile',
            'business_id' => $profile?->business_id,
            'photo_url' => $user->photoUrl(),
            'industry' => $profile?->displayIndustry() ?: null,
            'jobs_count' => $jobs ? (clone $jobs)->count() : 0,
            'active_jobs_count' => $jobs ? (clone $jobs)->where('active', true)->count() : 0,
            'applicants_count' => $profile?->applications()->count() ?? 0,
            'eligibility' => $this->employerEligibility->for($user),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function candidateDashboard(User $user): array
    {
        $profile = $user->candidateProfile;
        $resumeCount = 0;
        $primary = null;
        $documentCount = 0;
        $categories = [];
        $verifiedCount = 0;

        if ($profile) {
            $resumeCount = $profile->resumes()->count();
            $primaryResume = $profile->resumes()
                ->where('is_primary', true)
                ->first();
            $primary = $primaryResume instanceof CandidateResume
                ? $this->primaryResumeSummary($primaryResume)
                : null;

            $documentRows = CandidateDocument::query()
                ->where('candidate_profile_id', $profile->id)
                ->selectRaw('document_type, verification_status, COUNT(*) as aggregate')
                ->groupBy('document_type', 'verification_status')
                ->get();

            $documentCount = (int) $documentRows->sum('aggregate');
            $verifiedCount = (int) $documentRows
                ->filter(fn (CandidateDocument $row) => $this->isVerified($row->verification_status))
                ->sum('aggregate');
            $categories = $documentRows
                ->groupBy(fn (CandidateDocument $row) => $this->documentTypeValue($row->document_type))
                ->map(function ($group) {
                    return [
                        'label' => $this->documentTypeLabel($group->first()?->document_type),
                        'count' => (int) $group->sum('aggregate'),
                    ];
                })
                ->values()
                ->all();
        }

        $savedJobs = SavedJob::query()
            ->where('user_id', $user->id)
            ->with('job')
            ->latest('created_at');

        $applications = $user->applications()
            ->with('job')
            ->latest();

        return [
            'header' => [
                'name' => $user->name,
                'username' => $user->username,
                'photo_url' => $user->photoUrl(),
                'designation' => $profile?->current_designation,
                'location' => $user->location,
                'profile_completion' => (int) $user->profile_completion,
                'email_verified' => $user->emailVerified(),
            ],
            'resume' => [
                'count' => $resumeCount,
                'primary' => $primary,
            ],
            'documents' => [
                'count' => $documentCount,
                'categories' => $categories,
                'verified_count' => $verifiedCount,
            ],
            'saved_jobs' => [
                'count' => (clone $savedJobs)->count(),
                'recent' => (clone $savedJobs)
                    ->limit(3)
                    ->get()
                    ->map(fn (SavedJob $saved) => $saved->toAccountArray())
                    ->values()
                    ->all(),
            ],
            'applications' => [
                'count' => (clone $applications)->count(),
                'recent' => (clone $applications)
                    ->limit(5)
                    ->get()
                    ->map(fn (Application $application) => $this->applicationSummary($application))
                    ->values()
                    ->all(),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function primaryResumeSummary(CandidateResume $resume): array
    {
        return [
            'title' => $resume->title,
            'status' => $resume->status?->value,
            'status_label' => $resume->status?->label(),
            'is_primary' => true,
            'updated_at' => $resume->updated_at?->toDateTimeString(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function applicationSummary(Application $application): array
    {
        return [
            'title' => $application->job?->title ?? 'Role',
            'slug' => $application->job?->slug,
            'status' => $application->status->toCandidateLabel(),
            'applied_at' => $application->created_at?->toDateTimeString(),
            'href' => route('account.applications.show', $application, false),
        ];
    }

    private function isVerified(mixed $status): bool
    {
        if ($status instanceof DocumentVerificationStatus) {
            return $status === DocumentVerificationStatus::Verified;
        }

        return (string) $status === DocumentVerificationStatus::Verified->value;
    }

    private function documentTypeValue(mixed $type): string
    {
        if ($type instanceof DocumentType) {
            return $type->value;
        }

        return (string) $type;
    }

    private function documentTypeLabel(mixed $type): string
    {
        if ($type instanceof DocumentType) {
            return $type->label();
        }

        return DocumentType::tryFrom((string) $type)?->label() ?? 'Other';
    }
}
