<?php

namespace App\Http\Controllers;

use App\Http\Requests\Account\StoreCandidateResumeRequest;
use App\Http\Requests\StoreContactEnquiryRequest;
use App\Http\Requests\StoreEmployerEnquiryRequest;
use App\Models\Job;
use App\Models\User;
use App\Services\ApplicationSubmissionService;
use App\Services\CandidateResumeLibraryService;
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

        return Inertia::render('Home', [
            'content' => PublicContent::common(),
            'cms' => $cms->publicPage('home'),
            'services' => PublicContent::services(),
            'industries' => PublicContent::industries(),
            'featuredJobs' => $states->decorateCollection($featured, $request->user()),
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
                'featured' => $request->boolean('featured'),
            ],
            'options' => $jobs->publicFilterOptions(),
        ]);
    }

    public function jobShow(Request $request, Job $job, PublicJobStateService $states): Response
    {
        abort_unless($job->isPubliclyVisible(), 404);

        return Inertia::render('Jobs/Show', [
            'content' => PublicContent::common(),
            'job' => $states->decorate($job, $request->user(), true),
        ]);
    }

    public function jobApply(Request $request, Job $job, PublicJobStateService $states): Response|RedirectResponse
    {
        abort_unless($job->isPubliclyVisible(), 404);

        $user = $request->user();
        if (! $user instanceof User) {
            IntendedUrl::remember($request, route('jobs.show', $job, false));

            return redirect()->guest(route('login'));
        }

        if (! $user->isCandidate()) {
            abort(403);
        }

        $state = $states->decorate($job, $user, true);
        if (in_array($state['apply_state'], ['apply', 'applied', 'closed'], true)) {
            return redirect()->route('jobs.show', $job);
        }

        return Inertia::render('Jobs/Apply', [
            'content' => PublicContent::common(),
            'job' => $state,
            'needs_resume' => true,
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
            ->route('jobs.show', $job)
            ->with('success', 'Resume uploaded. You can apply now.');
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
