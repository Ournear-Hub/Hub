<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreApplicationRequest;
use App\Http\Requests\StoreContactEnquiryRequest;
use App\Http\Requests\StoreEmployerEnquiryRequest;
use App\Models\Job;
use App\Models\User;
use App\Services\ApplicationSubmissionService;
use App\Services\EnquirySubmissionService;
use App\Services\SubmissionMailService;
use App\Services\WebsiteContentService;
use App\Support\JobListingQuery;
use App\Support\PublicContent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PublicSiteController extends Controller
{
    public function home(JobListingQuery $jobs, WebsiteContentService $cms): Response
    {
        $featured = collect($jobs->featuredPublic())->map(fn (Job $job) => $job->toPublicCard())->values();

        return Inertia::render('Home', [
            'content' => PublicContent::common(),
            'cms' => $cms->publicPage('home'),
            'services' => PublicContent::services(),
            'industries' => PublicContent::industries(),
            'featuredJobs' => $featured,
        ]);
    }

    public function jobs(Request $request, JobListingQuery $jobs): Response
    {
        $listings = $jobs->publicIndex($request)->through(fn (Job $job) => $job->toPublicCard());

        return Inertia::render('Jobs/Index', [
            'content' => PublicContent::common(),
            'jobs' => $listings,
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

    public function jobShow(Job $job): Response
    {
        abort_unless($job->isPubliclyVisible(), 404);

        return Inertia::render('Jobs/Show', [
            'content' => PublicContent::common(),
            'job' => $job->toPublicDetail(),
        ]);
    }

    public function jobApply(Job $job): Response
    {
        abort_unless($job->isPubliclyVisible(), 404);

        return Inertia::render('Jobs/Apply', [
            'content' => PublicContent::common(),
            'job' => $job->toPublicDetail(),
            'canApply' => $job->acceptsApplications(),
            'closedMessage' => $job->acceptsApplications()
                ? null
                : 'This role is no longer accepting applications.',
            'prefill' => $this->candidateApplyPrefill(request()->user()),
        ]);
    }

    public function jobApplyStore(
        StoreApplicationRequest $request,
        Job $job,
        ApplicationSubmissionService $submissions,
        SubmissionMailService $mail,
    ): RedirectResponse {
        abort_unless($job->isPubliclyVisible(), 404);

        $application = $submissions->submit($job, $request->validated(), $request->file('resume'));
        $outcome = $mail->notifyApplication($application);

        return redirect()
            ->route('jobs.apply', $job)
            ->with('success', $outcome->publicMessage(
                'Thank you. Your application has been submitted.',
                'A confirmation email has been sent to you.',
            ));
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

    /**
     * @return array{full_name: string, mobile: string, email: string, current_location: string, experience: string, qualification: string}
     */
    private function candidateApplyPrefill(?User $user): array
    {
        $empty = [
            'full_name' => '',
            'mobile' => '',
            'email' => '',
            'current_location' => '',
            'experience' => '',
            'qualification' => '',
        ];

        if (! $user instanceof User || ! $user->isCandidate()) {
            return $empty;
        }

        $user->loadMissing('candidateProfile');
        $profile = $user->candidateProfile;

        return [
            'full_name' => (string) $user->name,
            'mobile' => (string) ($profile?->mobile ?? ''),
            'email' => (string) $user->email,
            'current_location' => (string) ($profile?->location ?? ''),
            'experience' => (string) ($profile?->experience ?? ''),
            'qualification' => (string) ($profile?->qualification ?? ''),
        ];
    }
}
