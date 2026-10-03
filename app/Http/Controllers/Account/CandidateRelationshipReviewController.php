<?php

namespace App\Http\Controllers\Account;

use App\Enums\RelationshipReviewDirection;
use App\Http\Controllers\Controller;
use App\Http\Requests\Account\StoreRelationshipReviewRequest;
use App\Models\CandidateProfile;
use App\Models\Employee;
use App\Models\RelationshipReview;
use App\Services\RelationshipReviewService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CandidateRelationshipReviewController extends Controller
{
    public function index(Request $request, RelationshipReviewService $reviews): Response
    {
        $this->profile($request);

        return Inertia::render('Account/Reviews/Index', [
            'relationships' => $reviews->workerWorkspace($request->user()),
        ]);
    }

    public function show(Request $request, RelationshipReview $review): RedirectResponse
    {
        $this->authorize('view', $review);
        $this->profile($request);

        return redirect()->route('account.reviews.index');
    }

    public function store(
        StoreRelationshipReviewRequest $request,
        Employee $employee,
        RelationshipReviewService $reviews,
    ): RedirectResponse {
        $this->profile($request);
        $this->authorize(
            'createForEmployee',
            [RelationshipReview::class, $employee, RelationshipReviewDirection::WorkerToEmployer],
        );

        $reviews->submit(
            $request->user(),
            $employee,
            RelationshipReviewDirection::WorkerToEmployer,
            (int) $request->validated('rating'),
            (string) $request->validated('body'),
        );

        return back()->with('success', 'Review submitted.');
    }

    private function profile(Request $request): CandidateProfile
    {
        $profile = $request->user()?->candidateProfile;
        abort_unless($profile instanceof CandidateProfile, 403);

        return $profile;
    }
}
