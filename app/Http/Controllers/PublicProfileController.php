<?php

namespace App\Http\Controllers;

use App\Models\EmployerProfile;
use App\Models\Job;
use App\Models\User;
use App\Services\PublicJobStateService;
use App\Services\UnifiedProfilePresenter;
use App\Support\PublicContent;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PublicProfileController extends Controller
{
    public function candidate(\Illuminate\Http\Request $request, User $user, UnifiedProfilePresenter $profiles): Response
    {
        abort_unless($user->isCandidate() && $user->is_active, 404);

        $viewer = $request->user('account');
        $isOwner = $viewer instanceof User && (int) $viewer->id === (int) $user->id;

        return Inertia::render('Profiles/Show', [
            'content' => PublicContent::common(),
            'profile' => $profiles->publicCandidate($user, $isOwner),
            'viewer' => [
                'is_owner' => $isOwner,
            ],
        ]);
    }

    public function company(
        Request $request,
        EmployerProfile $employer,
        UnifiedProfilePresenter $profiles,
        PublicJobStateService $jobStates,
    ): Response {
        $company = $profiles->publicCompany($employer);

        $openJobs = Job::query()
            ->with([
                'employer.user',
                'classifiedIndustry',
                'classifiedCategory',
                'classifiedLocation',
                'classifiedEmploymentType',
                'classifiedExperienceRange',
                'skills',
            ])
            ->where('employer_id', $employer->id)
            ->publiclyVisible()
            ->latest('posted_date')
            ->limit(8)
            ->get();

        return Inertia::render('Companies/Show', [
            'content' => PublicContent::common(),
            'company' => $company,
            'open_job_cards' => $jobStates->decorateCollection($openJobs, $request->user()),
        ]);
    }
}
