<?php

namespace App\Http\Controllers\Account;

use App\Enums\RelationshipReviewDirection;
use App\Http\Controllers\Controller;
use App\Http\Requests\Account\StoreRelationshipReviewRequest;
use App\Models\Employee;
use App\Models\EmployerProfile;
use App\Models\RelationshipReview;
use App\Services\RelationshipReviewService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EmployerRelationshipReviewController extends Controller
{
    public function index(Request $request, RelationshipReviewService $reviews): Response
    {
        $this->profile($request);

        return Inertia::render('Account/Employer/Reviews/Index', [
            'relationships' => $reviews->employerWorkspace($request->user()),
        ]);
    }

    public function show(Request $request, RelationshipReview $review): RedirectResponse
    {
        $this->authorize('view', $review);
        $this->profile($request);

        return redirect()->route('account.employer.reviews.index');
    }

    public function store(
        StoreRelationshipReviewRequest $request,
        Employee $employee,
        RelationshipReviewService $reviews,
    ): RedirectResponse {
        $this->profile($request);
        $this->authorize(
            'createForEmployee',
            [RelationshipReview::class, $employee, RelationshipReviewDirection::EmployerToWorker],
        );

        $reviews->submit(
            $request->user(),
            $employee,
            RelationshipReviewDirection::EmployerToWorker,
            (int) $request->validated('rating'),
            (string) $request->validated('body'),
        );

        return back()->with('success', 'Review submitted.');
    }

    private function profile(Request $request): EmployerProfile
    {
        $profile = $request->user()?->employerProfile;
        abort_unless($profile instanceof EmployerProfile, 403);

        return $profile;
    }
}
