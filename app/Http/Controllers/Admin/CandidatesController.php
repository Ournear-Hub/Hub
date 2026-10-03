<?php

namespace App\Http\Controllers\Admin;

use App\Enums\DocumentType;
use App\Enums\LanguageProficiency;
use App\Enums\NoticePeriod;
use App\Enums\PermissionName;
use App\Enums\ScreeningDecision;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RecordTalentInterestRequest;
use App\Http\Requests\Admin\StoreCandidateCertificationRequest;
use App\Http\Requests\Admin\StoreCandidateLanguageRequest;
use App\Http\Requests\Admin\StoreSavedSearchRequest;
use App\Http\Requests\Admin\SyncCandidateTagsRequest;
use App\Http\Requests\Admin\UpdateCandidateCertificationRequest;
use App\Http\Requests\Admin\UpdateCandidateLanguageRequest;
use App\Http\Requests\Admin\UpdateCandidateProfileRequest;
use App\Models\CandidateCertification;
use App\Models\CandidateDocument;
use App\Models\CandidateLanguage;
use App\Models\CandidateProfile;
use App\Models\CandidateResume;
use App\Models\ExperienceRange;
use App\Models\Job;
use App\Models\Location;
use App\Models\Qualification;
use App\Models\SavedSearch;
use App\Models\Skill;
use App\Models\Tag;
use App\Models\Task;
use App\Services\CandidatePrivateFileService;
use App\Services\CandidateProfessionalProfileService;
use App\Services\CandidateTalentService;
use App\Services\MasterData\MasterDataService;
use App\Services\TagService;
use App\Support\CandidateQuery;
use App\Support\CandidateTimeline;
use App\Support\StaffOptions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CandidatesController extends Controller
{
    public function index(
        Request $request,
        CandidateQuery $candidates,
        MasterDataService $masterData,
        CandidateTalentService $talent,
        CandidateTimeline $timeline,
    ): Response {
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

        $actor = $request->user();

        return Inertia::render('Admin/Candidates/Index', [
            'candidates' => $page,
            'selected' => $selectedPayload,
            'applications' => $selected ? $this->applications($selected) : [],
            'recruitment' => $selected ? $this->recruitment($selected) : null,
            'documents' => $selected ? $this->documents($selected) : null,
            'activities' => $selected ? $timeline->compose($selected) : [],
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
                'industry_id' => $request->string('industry_id')->toString(),
                'skill_id' => $request->string('skill_id')->toString(),
                'tag_id' => $request->string('tag_id')->toString(),
                'notice_period' => $request->string('notice_period')->toString(),
                'expected_salary_min' => $request->string('expected_salary_min')->toString(),
                'expected_salary_max' => $request->string('expected_salary_max')->toString(),
                'willing_to_relocate' => $request->string('willing_to_relocate')->toString(),
                'availability' => $request->string('availability')->toString(),
                'language' => $request->string('language')->toString(),
                'candidate' => $selected ? (string) $selected->id : '',
            ],
            'lookups' => [
                ...$masterData->formOptions(),
                'notice_periods' => NoticePeriod::options(),
                'language_proficiencies' => LanguageProficiency::options(),
                'skills' => Skill::query()->orderBy('name')->get(['id', 'name'])->map(fn (Skill $skill) => [
                    'id' => $skill->id,
                    'name' => $skill->name,
                ])->all(),
                'tags' => Tag::query()->orderBy('name')->get()->map(fn (Tag $tag) => $tag->toAdminArray())->all(),
            ],
            'jobs' => Job::query()->orderBy('title')->get(['id', 'title', 'business_id'])->map(fn (Job $job) => [
                'id' => $job->id,
                'title' => $job->title,
                'business_id' => $job->business_id,
            ])->all(),
            'saved_searches' => $actor ? $talent->searchesFor($actor) : [],
            'can_manage' => $actor?->hasPermission(PermissionName::CandidatesManage->value) === true,
            'can_create_task' => $actor?->can('create', Task::class) === true,
            'staffUsers' => StaffOptions::users(),
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
            'willing_to_relocate',
            'availability',
        ] as $field) {
            if ($field === 'willing_to_relocate' && array_key_exists($field, $data)) {
                $professionalAttributes[$field] = (bool) $data[$field];
                continue;
            }
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

    public function syncTags(
        SyncCandidateTagsRequest $request,
        CandidateProfile $candidate,
        TagService $tags,
    ): RedirectResponse {
        $user = $candidate->user;
        abort_unless($user !== null, 404);

        $tags->syncUserTags($request->user(), $user, $request->tagIds());

        return back()->with('success', 'Candidate tags updated.');
    }

    public function recordInterest(
        RecordTalentInterestRequest $request,
        CandidateProfile $candidate,
        CandidateTalentService $talent,
    ): RedirectResponse {
        $talent->recordInterest($candidate, $request->note(), $request->user());

        return back()->with('success', 'Interest recorded.');
    }

    public function storeCertification(
        StoreCandidateCertificationRequest $request,
        CandidateProfile $candidate,
        CandidateTalentService $talent,
    ): RedirectResponse {
        $talent->addCertification($candidate, $request->certificationAttributes(), $request->user());

        return back()->with('success', 'Certification added.');
    }

    public function updateCertification(
        UpdateCandidateCertificationRequest $request,
        CandidateProfile $candidate,
        CandidateCertification $certification,
        CandidateTalentService $talent,
    ): RedirectResponse {
        abort_unless((int) $certification->candidate_profile_id === (int) $candidate->id, 404);
        $talent->updateCertification($certification, $request->certificationAttributes(), $request->user());

        return back()->with('success', 'Certification updated.');
    }

    public function destroyCertification(
        CandidateProfile $candidate,
        CandidateCertification $certification,
        CandidateTalentService $talent,
    ): RedirectResponse {
        $this->authorize('update', $candidate);
        abort_unless((int) $certification->candidate_profile_id === (int) $candidate->id, 404);
        $talent->deleteCertification($certification, request()->user());

        return back()->with('success', 'Certification removed.');
    }

    public function storeLanguage(
        StoreCandidateLanguageRequest $request,
        CandidateProfile $candidate,
        CandidateTalentService $talent,
    ): RedirectResponse {
        $talent->addLanguage($candidate, $request->languageAttributes(), $request->user());

        return back()->with('success', 'Language added.');
    }

    public function updateLanguage(
        UpdateCandidateLanguageRequest $request,
        CandidateProfile $candidate,
        CandidateLanguage $language,
        CandidateTalentService $talent,
    ): RedirectResponse {
        abort_unless((int) $language->candidate_profile_id === (int) $candidate->id, 404);
        $talent->updateLanguage($language, $request->languageAttributes(), $request->user());

        return back()->with('success', 'Language updated.');
    }

    public function destroyLanguage(
        CandidateProfile $candidate,
        CandidateLanguage $language,
        CandidateTalentService $talent,
    ): RedirectResponse {
        $this->authorize('update', $candidate);
        abort_unless((int) $language->candidate_profile_id === (int) $candidate->id, 404);
        $talent->deleteLanguage($language, request()->user());

        return back()->with('success', 'Language removed.');
    }

    public function storeSavedSearch(
        StoreSavedSearchRequest $request,
        CandidateTalentService $talent,
    ): RedirectResponse {
        $talent->saveSearch($request->user(), $request->string('name')->toString(), $request->filters());

        return back()->with('success', 'Search saved.');
    }

    public function destroySavedSearch(
        Request $request,
        SavedSearch $savedSearch,
        CandidateTalentService $talent,
    ): RedirectResponse {
        $this->authorize('viewAny', CandidateProfile::class);
        $talent->deleteSearch($request->user(), $savedSearch);

        return back()->with('success', 'Saved search removed.');
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

    private function selectedProfile(Request $request): ?CandidateProfile
    {
        $id = $request->integer('candidate');
        if ($id <= 0) {
            return null;
        }

        $profile = CandidateProfile::query()
            ->with([
                'user.tags',
                'industry',
                'jobCategory',
                'skills',
                'employmentHistories.industry',
                'preferredLocations',
                'preferredEmploymentTypes',
                'certifications',
                'languages',
            ])
            ->withCount('applications')
            ->find($id);

        if ($profile && $request->user()?->can('view', $profile)) {
            return $profile;
        }

        return null;
    }
}
