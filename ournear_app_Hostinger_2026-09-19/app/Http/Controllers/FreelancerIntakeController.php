<?php

namespace App\Http\Controllers;

use App\Enums\FreelancerStatus;
use App\Http\Requests\StoreFreelancerRequest;
use App\Models\Freelancer;
use App\Services\FreelancerIntakeService;
use App\Services\SubmissionMailService;
use App\Support\MailOutcome;
use App\Support\PublicContent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FreelancerIntakeController extends Controller
{
    public function create(Request $request, FreelancerIntakeService $intake): Response
    {
        $partners = Freelancer::query()
            ->with('industry')
            ->where('status', '!=', FreelancerStatus::Rejected)
            ->orderByDesc('submitted_at')
            ->limit(24)
            ->get()
            ->map(fn (Freelancer $freelancer) => $freelancer->toPublicCard())
            ->values()
            ->all();

        return Inertia::render('Freelancers/Index', [
            'content' => PublicContent::common(),
            'industries' => PublicContent::industries(),
            'submission_token' => $intake->token($request),
            'options' => $intake->formOptions(),
            'partners' => $partners,
        ]);
    }

    public function store(
        StoreFreelancerRequest $request,
        FreelancerIntakeService $intake,
        SubmissionMailService $mail,
    ): RedirectResponse {
        $freelancer = $intake->submit(
            $request->validated(),
            $request->file('resume'),
            $request->file('certificate'),
            $request,
        );
        $outcome = $freelancer->wasRecentlyCreated
            ? $mail->notifyFreelancer($freelancer)
            : new MailOutcome(false);

        return redirect()
            ->route('freelancers.received')
            ->with('freelancer_confirmation', $freelancer->toConfirmationArray())
            ->with('success', $outcome->publicMessage(
                'Partnership registration received.',
                'A confirmation email has been sent to you.',
            ));
    }

    public function received(Request $request): Response|RedirectResponse
    {
        $confirmation = $request->session()->get('freelancer_confirmation');
        if (! is_array($confirmation) || blank($confirmation['business_id'] ?? null)) {
            return redirect()->route('freelancers');
        }

        return Inertia::render('Freelancers/Received', [
            'content' => PublicContent::common(),
            'confirmation' => $confirmation,
            'message' => $request->session()->get('success'),
        ]);
    }
}
