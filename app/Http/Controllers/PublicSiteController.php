<?php

namespace App\Http\Controllers;

use App\Http\Requests\Account\StoreCandidateResumeRequest;
use App\Http\Requests\StoreContactEnquiryRequest;
use App\Http\Requests\StoreEmployerEnquiryRequest;
use App\Models\CandidateResume;
use App\Models\EmployerProfile;
use App\Models\Job;
use App\Models\User;
use App\Services\ApplicationSubmissionService;
use App\Services\CandidateResumeLibraryService;
use App\Services\ShareTrackingService;
use App\Services\EnquirySubmissionService;
use App\Services\PublicJobStateService;
use App\Services\SavedJobService;
use App\Services\SubmissionMailService;
use App\Services\WebsiteContentService;
use App\Support\IntendedUrl;
use App\Support\JobListingQuery;
use App\Support\PublicContent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class PublicSiteController extends Controller
{
    public function home(Request $request, JobListingQuery $jobs, PublicJobStateService $states, WebsiteContentService $cms): Response
    {
        $featured = collect($jobs->featuredPublic());
        $latest = Job::query()->with('employer.user')->publiclyVisible()->latest('id')->limit(6)->get();

        $companies = EmployerProfile::query()
            ->with(['user', 'locations'])
            ->withCount(['jobs as open_jobs_count' => fn ($query) => $query->publiclyVisible()])
            ->orderByDesc('open_jobs_count')
            ->orderBy('company_name')
            ->limit(20)
            ->get()
            ->filter(
                fn (EmployerProfile $employer) => $employer->user?->is_active === true
                    && $employer->shareAllows('enabled'),
            )
            ->take(6)
            ->map(fn (EmployerProfile $employer) => [
                'id' => $employer->id,
                'name' => $employer->publicDisplayCompany(),
                'industry' => $employer->shareAllows('industry_size') ? $employer->displayIndustry() : null,
                'location' => $employer->shareAllows('locations')
                    ? $employer->locations->pluck('name')->filter()->implode(', ') ?: null
                    : null,
                'logo_url' => $employer->user?->photoUrl(),
                'ournear_verified' => $employer->ournearVerified(),
                'open_jobs' => (int) $employer->open_jobs_count,
                'url' => route('companies.show', $employer),
            ])
            ->values();

        return Inertia::render('Home', [
            'content' => PublicContent::common(),
            'cms' => $cms->publicPage('home'),
            'services' => PublicContent::services(),
            'industries' => PublicContent::industries(),
            'featuredJobs' => $states->decorateCollection($featured, $request->user()),
            'latestJobs' => $states->decorateCollection($latest, $request->user()),
            'companies' => $companies,
        ]);
    }

    public function jobs(Request $request, JobListingQuery $jobs, PublicJobStateService $states): Response
    {
        $listings = $jobs->publicIndex($request);
        $pageJobs = $listings->getCollection();

        $selectedSlug = $request->string('job')->toString();
        $selected = $selectedSlug !== ''
            ? Job::query()->with('employer.user')->publiclyVisible()->where('slug', $selectedSlug)->first()
            : null;

        return Inertia::render('Jobs/Index', [
            'content' => PublicContent::common(),
            'jobs' => $listings->through(fn (Job $job) => $states->decorate($job, $request->user())),
            'selected' => $selected ? $states->decorate($selected, $request->user(), true) : null,
            'filters' => [
                'keyword' => $request->string('keyword')->toString(),
                'location' => $request->string('location')->toString(),
                'industry' => $request->string('industry')->toString(),
                'category' => $request->string('category')->toString(),
                'employment_type' => $request->string('employment_type')->toString(),
                'experience' => $request->string('experience')->toString(),
                'company' => $request->string('company')->toString(),
                'salary' => $request->string('salary')->toString(),
                'work_mode' => $request->string('work_mode')->toString(),
                'sort' => $request->string('sort')->toString(),
                'featured' => $request->boolean('featured'),
            ],
            'options' => $jobs->publicFilterOptions(),
        ]);
    }

    public function jobShow(Request $request, Job $job, PublicJobStateService $states, JobListingQuery $jobs): Response
    {
        abort_unless($job->isPubliclyVisible(), 404);

        $similar = collect($jobs->similar($job));

        return Inertia::render('Jobs/Show', [
            'content' => PublicContent::common(),
            'job' => $states->decorate($job, $request->user(), true),
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
            IntendedUrl::remember($request, route('jobs.apply', $job, false));

            return redirect()->guest(route('login'));
        }

        if (! $user->isCandidate()) {
            abort(403);
        }

        $state = $states->decorate($job, $user, true);
        if (in_array($state['apply_state'], ['applied', 'closed'], true)) {
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
            IntendedUrl::remember($request, route('jobs.show', $job, false));

            return redirect()->guest(route('login'));
        }

        if (! $user->isCandidate()) {
            abort(403);
        }

        try {
            $application = $submissions->submitFromProfile($job, $user);
        } catch (ValidationException $exception) {
            if (isset($exception->errors()['resume'])) {
                return redirect()
                    ->route('jobs.apply', $job)
                    ->withErrors($exception->errors());
            }

            return redirect()
                ->route('jobs.show', $job)
                ->withErrors($exception->errors());
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
        $enquiry = $enquiries->storeEmployerEnquiry($request->validated());
        $outcome = $mail->notifyEmployerEnquiry($enquiry);

        return redirect()
            ->route('for-employers')
            ->with('success', $outcome->publicMessage(
                'Thank you. Your employer enquiry has been submitted.',
                'A confirmation email has been sent to you.',
            ));
    }

    public function contact(WebsiteContentService $cms): Response
    {
        return Inertia::render('Contact', [
            'content' => PublicContent::common(),
            'cms' => $cms->publicPage('contact'),
            'interestOptions' => [
                'Recruitment',
                'Training',
                'Workforce solutions',
                'General enquiry',
            ],
        ]);
    }

    public function storeContactEnquiry(
        StoreContactEnquiryRequest $request,
        EnquirySubmissionService $enquiries,
        SubmissionMailService $mail,
    ): RedirectResponse {
        $enquiry = $enquiries->storeContactEnquiry($request->validated());
        $outcome = $mail->notifyContactEnquiry($enquiry);

        return redirect()
            ->route('contact')
            ->with('success', $outcome->publicMessage(
                'Thank you. Your message has been sent.',
                'A confirmation email has been sent to you.',
            ));
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
