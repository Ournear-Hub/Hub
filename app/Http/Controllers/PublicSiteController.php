<?php

namespace App\Http\Controllers;

use App\Http\Requests\Account\StoreCandidateResumeRequest;
use App\Http\Requests\StoreEmployerEnquiryRequest;
use App\Models\CandidateResume;
use App\Models\EmployerProfile;
use App\Models\Job;
use App\Models\User;
use App\Services\ApplicationSubmissionService;
use App\Services\CandidateResumeLibraryService;
use App\Services\HomepagePresentationService;
use App\Services\JobSearchAnalyticsService;
use App\Services\PopularJobSearchService;
use App\Services\ShareTrackingService;
use App\Services\EnquirySubmissionService;
use App\Services\PublicJobStateService;
use App\Services\SavedJobService;
use App\Services\SubmissionMailService;
use App\Services\UnifiedProfilePresenter;
use App\Services\ShowcaseContentService;
use App\Services\WebsiteContentService;
use App\Support\JobListingQuery;
use App\Support\PublicContent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class PublicSiteController extends Controller
{
    public function home(
        Request $request,
        JobListingQuery $jobs,
        PublicJobStateService $states,
        WebsiteContentService $cms,
        HomepagePresentationService $homepage,
        PopularJobSearchService $popularSearches,
        ShowcaseContentService $showcase,
    ): Response {
        $featured = collect($jobs->featuredPublic());
        $latest = Job::query()
            ->with([
                'employer.user',
                'classifiedIndustry',
                'classifiedCategory',
                'classifiedLocation',
                'classifiedEmploymentType',
                'classifiedExperienceRange',
            ])
            ->publiclyVisible()
            ->latest('id')
            ->limit(6)
            ->get();

        return Inertia::render('Home', [
            'content' => PublicContent::common(),
            'cms' => $cms->publicPage('home'),
            'services' => PublicContent::services(),
            'heroSlides' => $homepage->heroSlides(),
            'popularSearchDiscovery' => $popularSearches->homepageDiscovery(),
            'hiringFeatures' => $homepage->audienceFeatureCards('hiring_features'),
            'candidateFeatures' => $homepage->audienceFeatureCards('candidate_features'),
            'industries' => $homepage->industries(),
            'promoBanner' => $homepage->promotionalBanner(),
            'latestJobsSection' => $homepage->latestJobsSection(),
            'featuredJobs' => $states->decorateCollection($featured, $request->user()),
            'latestJobs' => $states->decorateCollection($latest, $request->user()),
            'homeGlanceStats' => $showcase->statistics()->toPublicGlance(),
            'homeShowcaseCompanies' => $showcase->publicCompaniesFor('home'),
        ]);
    }

    public function jobs(
        Request $request,
        JobListingQuery $jobs,
        PublicJobStateService $states,
        JobSearchAnalyticsService $searchAnalytics,
        UnifiedProfilePresenter $profiles,
    ): Response {
        $searchAnalytics->recordFromRequest($request);

        $listings = $jobs->publicIndex($request);
        $pageJobs = $listings->getCollection();

        $selectedSlug = trim($request->string('job')->toString());
        if ($selectedSlug === '' && $pageJobs->isNotEmpty()) {
            $selectedSlug = (string) $pageJobs->first()->slug;
        }

        return Inertia::render('Jobs/Index', [
            'content' => PublicContent::common(),
            'jobs' => $listings->through(fn (Job $job) => $states->decorate($job, $request->user())),
            'selected_job' => $this->resolvePublicJobsSelection($request, $states, $profiles, $selectedSlug),
            'filters' => $this->publicJobsFilters($request, $jobs),
            'options' => $jobs->publicFilterOptions(),
        ]);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function resolvePublicJobsSelection(
        Request $request,
        PublicJobStateService $states,
        UnifiedProfilePresenter $profiles,
        string $slug,
    ): ?array {
        if ($slug === '') {
            return null;
        }

        $job = Job::query()
            ->publiclyVisible()
            ->where('slug', $slug)
            ->first();

        if (! $job instanceof Job) {
            return null;
        }

        $job->loadMissing([
            'employer.user',
            'employer.locations',
            'employer.classifiedIndustry',
            'skills',
            'qualifications',
        ]);

        $payload = $states->decorate($job, $request->user(), true);
        $payload['company_profile'] = $profiles->publicCompanyForJobTab($job->employer);

        return $payload;
    }

    /**
     * @return array<string, mixed>
     */
    private function publicJobsFilters(Request $request, JobListingQuery $jobs): array
    {
        $company = trim($request->string('company')->toString());
        $employerLabel = $jobs->employerFilterLabel($request);

        return [
            'keyword' => $request->string('keyword')->toString(),
            'location' => $request->string('location')->toString(),
            'industry' => $request->string('industry')->toString(),
            'category' => $request->string('category')->toString(),
            'employment_type' => $request->string('employment_type')->toString(),
            'experience' => $request->string('experience')->toString(),
            'company' => $company !== '' ? $company : ($employerLabel ?? ''),
            'salary' => $request->string('salary')->toString(),
            'work_mode' => $request->string('work_mode')->toString(),
            'sort' => $request->string('sort')->toString(),
            'featured' => $request->has('featured') ? $request->boolean('featured') : false,
        ];
    }

    public function jobShow(
        Request $request,
        Job $job,
        PublicJobStateService $states,
        JobListingQuery $jobs,
        UnifiedProfilePresenter $profiles,
    ): Response {
        abort_unless($job->isPubliclyVisible(), 404);

        $job->loadMissing([
            'employer.user',
            'employer.locations',
            'employer.classifiedIndustry',
            'skills',
            'qualifications',
        ]);

        $similar = collect($jobs->similar($job));
        $jobPayload = $states->decorate($job, $request->user(), true);
        $jobPayload['company_profile'] = $profiles->publicCompanyForJobTab($job->employer);

        return Inertia::render('Jobs/Show', [
            'content' => PublicContent::common(),
            'job' => $jobPayload,
            'similar_jobs' => $states->decorateCollection($similar, $request->user()),
        ]);
    }

    public function jobShare(Request $request, Job $job, ShareTrackingService $shares): RedirectResponse
    {
        abort_unless($job->isPubliclyVisible(), 404);

        $shares->recordJobShare($job, (string) $request->input('channel'), $request);

        return back();
    }

    public function jobApply(Request $request, Job $job, PublicJobStateService $states): Response|RedirectResponse
    {
        abort_unless($job->isPubliclyVisible(), 404);

        $user = $request->user();
        if (! $user instanceof User) {
            return redirect()->guest(route('login'));
        }

        if (! $user->isCandidate()) {
            abort(403);
        }

        $state = $states->decorate($job, $user, true);
        if (in_array($state['apply_state'], ['applied', 'closed', 'own_company'], true)) {
            return redirect()->route('jobs.show', $job);
        }

        $resumePayload = null;
        if ($state['has_resume']) {
            $resumeId = $states->primaryResumeId($user);
            $resumeModel = $resumeId ? CandidateResume::query()->find($resumeId) : null;
            if ($resumeModel !== null) {
                $resumePayload = [
                    'title' => (string) $resumeModel->title,
                    'filename' => (string) $resumeModel->original_filename,
                ];
            }
        }

        $profileGaps = collect($user->profileCompletionItems())
            ->filter(fn (array $item): bool => ($item['complete'] ?? false) === false)
            ->map(fn (array $item): array => [
                'key' => (string) $item['key'],
                'label' => (string) $item['label'],
            ])
            ->take(6)
            ->values()
            ->all();

        return Inertia::render('Jobs/Apply', [
            'job' => $state,
            'needs_resume' => ! $state['has_resume'],
            'resume' => $resumePayload,
            'profile_gaps' => $profileGaps,
            'requires_email_verification' => ! $user->hasVerifiedEmail(),
        ]);
    }

    public function jobApplyStore(
        Request $request,
        Job $job,
        ApplicationSubmissionService $submissions,
        SubmissionMailService $mail,
    ): RedirectResponse {
        abort_unless($job->isPubliclyVisible(), 404);

        $user = $request->user();
        if (! $user instanceof User) {
            return redirect()->guest(route('login'));
        }

        if (! $user->isCandidate()) {
            abort(403);
        }

        try {
            $application = $submissions->submitFromProfile($job, $user);
        } catch (ValidationException $exception) {
            $errors = $exception->errors();
            if (isset($errors['resume']) || isset($errors['email'])) {
                return redirect()
                    ->route('jobs.apply', $job)
                    ->withErrors($errors);
            }

            return redirect()
                ->route('jobs.show', $job)
                ->withErrors($errors);
        }

        $outcome = $mail->notifyApplication($application);

        return redirect()
            ->route('jobs.show', $job)
            ->with('success', $outcome->publicMessage(
                'Thank you. Your application has been submitted.',
                'A confirmation email has been sent to you.',
            ))
            ->with('application_confirmation', [
                'business_id' => $application->business_id,
                'job_title' => $job->title,
                'company' => $job->project,
                'location' => $job->location,
                'href' => route('account.applications.show', $application, false),
            ]);
    }

    public function jobApplyResume(
        StoreCandidateResumeRequest $request,
        Job $job,
        CandidateResumeLibraryService $library,
    ): RedirectResponse {
        abort_unless($job->isPubliclyVisible(), 404);

        $user = $request->user();
        abort_unless($user instanceof User && $user->isCandidate(), 403);
        $user->loadMissing('candidateProfile');
        abort_unless($user->candidateProfile !== null, 404);

        $library->upload(
            $user->candidateProfile,
            $request->file('file'),
            $request->validated(),
            $user,
        );

        return redirect()
            ->route('jobs.apply', $job)
            ->with('success', 'Resume uploaded. Review and submit your application.');
    }

    public function about(WebsiteContentService $cms): Response
    {
        return Inertia::render('About', [
            'content' => PublicContent::common(),
            'cms' => $cms->publicPage('about'),
            'industries' => PublicContent::industries(),
        ]);
    }

    public function services(WebsiteContentService $cms): Response
    {
        return Inertia::render('Services', [
            'content' => PublicContent::common(),
            'cms' => $cms->publicPage('services'),
            'services' => PublicContent::services(),
        ]);
    }

    public function forEmployers(WebsiteContentService $cms): Response
    {
        return Inertia::render('ForEmployers', [
            'content' => PublicContent::common(),
            'cms' => $cms->publicPage('for-employers'),
            'services' => PublicContent::services(),
            'industries' => PublicContent::industries(),
            'workforceTypes' => [
                'Permanent',
                'Contract',
                'Temporary',
                'Project teams',
            ],
        ]);
    }

    public function storeEmployerEnquiry(
        StoreEmployerEnquiryRequest $request,
        EnquirySubmissionService $enquiries,
        SubmissionMailService $mail,
    ): RedirectResponse {
        $validated = $request->validated();
        unset($validated['from_staffing']);
        $enquiry = $enquiries->storeEmployerEnquiry($validated);
        $outcome = $mail->notifyEmployerEnquiry($enquiry);

        $isStaffing = $request->input('from_staffing') === '1';

        $message = $outcome->publicMessage(
            $isStaffing
                ? 'Your staffing requirement has been submitted. Reference #'.$enquiry->id.'.'
                : 'Thank you. Your employer enquiry has been submitted.',
            'A confirmation email has been sent to you when delivery is available.',
        );

        if ($isStaffing) {
            return redirect()
                ->route('staffing')
                ->withFragment('request-manpower')
                ->with('success', $message);
        }

        return redirect()
            ->route('for-employers')
            ->with('success', $message);
    }

    public function contact(): Response
    {
        return Inertia::render('Contact', [
            'content' => PublicContent::common(),
        ]);
    }

    public function storeContactEnquiry(): never
    {
        abort(404);
    }

    public function privacy(WebsiteContentService $cms): Response
    {
        return Inertia::render('Legal/PrivacyPolicy', [
            'content' => PublicContent::common(),
            'cms' => $cms->publicPage('privacy-policy'),
        ]);
    }

    public function terms(WebsiteContentService $cms): Response
    {
        return Inertia::render('Legal/Terms', [
            'content' => PublicContent::common(),
            'cms' => $cms->publicPage('terms-and-conditions'),
        ]);
    }
}
