<?php

namespace App\Http\Controllers\Admin;

use App\Enums\DocumentType;
use App\Enums\NoticePeriod;
use App\Enums\ScreeningDecision;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateCandidateProfileRequest;
use App\Models\CandidateDocument;
use App\Models\CandidateProfile;
use App\Models\CandidateResume;
use App\Models\ExperienceRange;
use App\Models\Job;
use App\Models\Location;
use App\Models\Qualification;
use App\Models\RecruitmentActivity;
use App\Services\CandidatePrivateFileService;
use App\Services\CandidateProfessionalProfileService;
use App\Services\MasterData\MasterDataService;
use App\Support\CandidateQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CandidatesController extends Controller
{
    public function index(Request $request, CandidateQuery $candidates, MasterDataService $masterData): Response
    {
        $this->authorize('viewAny', CandidateProfile::class);

        $page = $candidates->adminIndex($request)
            ->through(fn (CandidateProfile $profile) => $profile->toAdminArray());

        $selected = $this->selectedProfile($request);
        $selectedPayload = $selected?->toAdminArray();
        if (is_array($selectedPayload) && $selected) {
            $selectedPayload['links'] = [
                'applications' => route('admin.applications.index', ['candidate' => $selected->id], false),
                'jobs' => route('admin.applications.index', ['candidate' => $selected->id], false),
                'screening' => route('admin.screening.index', ['candidate' => $selected->id], false),
                'user' => $selected->user_id ? route('admin.users.show', $selected->user_id, false) : null,
            ];
        }

        return Inertia::render('Admin/Candidates/Index', [
            'candidates' => $page,
            'selected' => $selectedPayload,
            'applications' => $selected ? $this->applications($selected) : [],
            'recruitment' => $selected ? $this->recruitment($selected) : null,
            'documents' => $selected ? $this->documents($selected) : null,
            'activities' => $selected ? $this->activities($selected) : [],
            'filters' => [
                'search' => $request->string('search')->toString(),
                'location_id' => $request->string('location_id')->toString(),
                'experience_range_id' => $request->string('experience_range_id')->toString(),
                'qualification_id' => $request->string('qualification_id')->toString(),
                'date_from' => $request->string('date_from')->toString(),
                'date_to' => $request->string('date_to')->toString(),
                'job_id' => $request->string('job_id')->toString(),
                'status' => $request->string('status')->toString(),
                'verification' => $request->string('verification')->toString(),
                'candidate' => $selected ? (string) $selected->id : '',
            ],
            'lookups' => [
                ...$masterData->formOptions(),
                'notice_periods' => NoticePeriod::options(),
            ],
            'jobs' => Job::query()->orderBy('title')->get(['id', 'title', 'business_id'])->map(fn (Job $job) => [
                'id' => $job->id,
                'title' => $job->title,
                'business_id' => $job->business_id,
            ])->all(),
        ]);
    }

    public function update(
        UpdateCandidateProfileRequest $request,
        CandidateProfile $candidate,
        CandidateProfessionalProfileService $professional,
    ): RedirectResponse {
        $data = $request->validated();

        $candidate->user?->update(['name' => $data['name']]);

        $this->syncLookup($candidate, 'location_id', 'location', Location::class, $data['location_id'] ?? null);
        $this->syncLookup($candidate, 'experience_range_id', 'experience', ExperienceRange::class, $data['experience_range_id'] ?? null);
        $this->syncLookup($candidate, 'qualification_id', 'qualification', Qualification::class, $data['qualification_id'] ?? null);
        $candidate->mobile = $data['mobile'] ?? null;
        $candidate->save();

        $professionalAttributes = [];
        foreach ([
            'current_designation',
            'current_company',
            'industry_id',
            'job_category_id',
            'current_salary',
            'expected_salary',
            'notice_period',
        ] as $field) {
            if (array_key_exists($field, $data)) {
                $professionalAttributes[$field] = $data[$field];
            }
        }

        if ($professionalAttributes !== []) {
            $professional->updateProfessional($candidate, $professionalAttributes, $request->user());
        } else {
            $professional->refreshCompletion($candidate);
        }

        return back()->with('success', 'Candidate profile updated.');
    }

    /**
     * @param  class-string  $model
     */
    private function syncLookup(CandidateProfile $profile, string $idKey, string $stringKey, string $model, mixed $id): void
    {
        if (filled($id)) {
            $record = $model::query()->find($id);
            $profile->{$idKey} = $record?->id;
            if ($record) {
                $profile->{$stringKey} = $record->name;
            }

            return;
        }

        $profile->{$idKey} = null;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function applications(CandidateProfile $profile): array
    {
        return $profile->applications()
            ->with(['job.employer.user', 'screening', 'interview', 'submissions', 'placements'])
            ->latest()
            ->limit(20)
            ->get()
            ->map(fn ($application) => $application->toAdminListArray())
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function recruitment(CandidateProfile $profile): array
    {
        $applications = $profile->applications()
            ->with(['job.employer.user', 'screening', 'interview', 'submissions.employer', 'placements'])
            ->latest()
            ->get();

        $jobs = $applications
            ->pluck('job')
            ->filter()
            ->unique('id')
            ->map(fn ($job) => [
                'id' => $job->id,
                'business_id' => $job->business_id,
                'title' => $job->title,
                'slug' => $job->slug,
                'workspace_url' => route('admin.jobs.index', ['job' => $job->slug], false),
            ])
            ->values()
            ->all();

        $screening = $applications->map(fn ($application) => [
            'id' => $application->id,
            'business_id' => $application->business_id,
            'job_title' => $application->job?->title,
            'decision' => $application->screeningDecision()->label(),
            'href' => route('admin.screening.index', ['application' => $application->id], false),
        ])->values()->all();

        $shortlisted = $applications
            ->filter(fn ($application) => $application->screeningDecision() === ScreeningDecision::Shortlisted
                || $application->status->value === 'Shortlisted')
            ->map(fn ($application) => [
                'id' => $application->id,
                'business_id' => $application->business_id,
                'job_title' => $application->job?->title,
                'href' => route('admin.shortlisted.index', ['application' => $application->id], false),
            ])
            ->values()
            ->all();

        $interviews = $applications
            ->filter(fn ($application) => $application->interview !== null)
            ->map(fn ($application) => [
                'id' => $application->interview?->id,
                'application_id' => $application->id,
                'job_title' => $application->job?->title,
                'scheduled_at' => $application->interview?->scheduled_at?->toDateTimeString(),
                'outcome' => $application->interview?->outcome?->value,
                'href' => route('admin.applications.index', ['application' => $application->id], false),
            ])
            ->values()
            ->all();

        $submissions = $applications
            ->flatMap(fn ($application) => $application->submissions)
            ->map(fn ($submission) => $submission->toAdminListArray())
            ->values()
            ->all();

        $placements = $applications
            ->flatMap(fn ($application) => $application->placements)
            ->map(fn ($placement) => $placement->toAdminListArray())
            ->values()
            ->all();

        $selections = collect($submissions)
            ->filter(fn (array $submission) => ($submission['status'] ?? null) === 'Selected')
            ->values()
            ->all();

        return [
            'jobs' => $jobs,
            'applications' => $applications->map->toAdminListArray()->values()->all(),
            'screening' => $screening,
            'shortlisted' => $shortlisted,
            'interviews' => $interviews,
            'submissions' => $submissions,
            'selections' => $selections,
            'placements' => $placements,
        ];
    }

    public function showResume(
        Request $request,
        CandidateResume $resume,
        CandidatePrivateFileService $files,
    ): StreamedResponse {
        $profile = $resume->candidateProfile;
        abort_unless($profile instanceof CandidateProfile, 404);
        $this->authorize('view', $profile);
        $this->authorize('download', $resume);

        return $files->download(
            $resume->storage_path,
            $resume->original_filename ?: 'resume.'.$resume->extension,
            $request->string('disposition')->toString() === 'inline',
            $resume->storage_disk,
        );
    }

    public function showDocument(
        Request $request,
        CandidateDocument $document,
        CandidatePrivateFileService $files,
    ): StreamedResponse {
        $profile = $document->candidateProfile;
        abort_unless($profile instanceof CandidateProfile, 404);
        $this->authorize('view', $profile);
        $this->authorize('download', $document);

        return $files->download(
            $document->storage_path,
            $document->original_filename ?: 'document.'.$document->extension,
            $request->string('disposition')->toString() === 'inline',
            $document->storage_disk,
        );
    }

    /**
     * @return array<string, list<array<string, mixed>>>
     */
    private function documents(CandidateProfile $profile): array
    {
        $resumes = $profile->applications()
            ->with('job')
            ->latest()
            ->get()
            ->filter(fn ($application) => filled($application->getRawOriginal('resume_path') ?? $application->getAttributes()['resume_path'] ?? null))
            ->map(fn ($application) => [
                'id' => $application->id,
                'label' => ($application->job?->title ?: 'Application').' resume',
                'application_id' => $application->business_id,
                'view_url' => $application->adminResumeViewUrl(),
                'download_url' => $application->adminResumeUrl(),
            ])
            ->values()
            ->all();

        $libraryResumes = $profile->resumes()
            ->get()
            ->map->toAdminArray()
            ->values()
            ->all();

        $libraryDocuments = $profile->documents()
            ->get()
            ->map->toAdminArray();

        return [
            'resumes' => $resumes,
            'library_resumes' => $libraryResumes,
            'certificates' => $libraryDocuments->where('document_type', DocumentType::Certificate->value)->values()->all(),
            'licences' => $libraryDocuments->where('document_type', DocumentType::Licence->value)->values()->all(),
            'qualifications' => $libraryDocuments->where('document_type', DocumentType::Qualification->value)->values()->all(),
            'payslips' => $libraryDocuments->where('document_type', DocumentType::Payslip->value)->values()->all(),
            'experience' => $libraryDocuments->where('document_type', DocumentType::Experience->value)->values()->all(),
            'other' => $libraryDocuments->where('document_type', DocumentType::Other->value)->values()->all(),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function activities(CandidateProfile $profile): array
    {
        $applicationIds = $profile->applications()->pluck('id');

        if ($applicationIds->isEmpty()) {
            return [];
        }

        return RecruitmentActivity::query()
            ->with('actor:id,name')
            ->whereIn('application_id', $applicationIds)
            ->latest()
            ->limit(20)
            ->get()
            ->map(fn (RecruitmentActivity $activity) => [
                'id' => $activity->id,
                'title' => $activity->body ?: ($activity->from_status && $activity->to_status
                    ? $activity->from_status.' → '.$activity->to_status
                    : $activity->type->value),
                'meta' => trim(implode(' · ', array_filter([
                    $activity->actor?->name,
                    $activity->created_at?->toDateTimeString(),
                ]))),
            ])
            ->values()
            ->all();
    }

    private function selectedProfile(Request $request): ?CandidateProfile
    {
        $id = $request->integer('candidate');
        if ($id <= 0) {
            return null;
        }

        $profile = CandidateProfile::query()
            ->with([
                'user',
                'industry',
                'jobCategory',
                'skills',
                'employmentHistories.industry',
                'preferredLocations',
                'preferredEmploymentTypes',
            ])
            ->withCount('applications')
            ->find($id);

        if ($profile && $request->user()?->can('view', $profile)) {
            return $profile;
        }

        return null;
    }
}
