<?php

namespace App\Http\Controllers\Account;

use App\Enums\ApplicationStatus;
use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\EmployerProfile;
use App\Models\Job;
use App\Support\EmployerApplicationPipeline;
use App\Support\EmployerVisibleCandidate;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EmployerHiringWorkspaceController extends Controller
{
    public function applications(Request $request): Response
    {
        $profile = $this->profile($request);

        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', 'string', 'in:'.implode(',', array_column(ApplicationStatus::cases(), 'value'))],
        ]);

        $search = trim((string) ($validated['q'] ?? ''));
        $status = $validated['status'] ?? null;

        $query = $profile->applications()
            ->with(['user.candidateProfile', 'job']);

        if ($search !== '') {
            $like = '%'.$search.'%';
            $query->where(function ($builder) use ($like) {
                $builder->where('full_name', 'like', $like)
                    ->orWhereHas('user', fn ($user) => $user->where('name', 'like', $like));
            });
        }

        if (is_string($status) && $status !== '') {
            $query->where('status', $status);
        }

        $applications = $query
            ->latest('applications.id')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Application $application) => EmployerVisibleCandidate::fromApplication($application));

        return Inertia::render('Account/Hiring/Applications/Index', [
            'company_name' => $profile->displayCompany(),
            'applications' => $applications,
            'filters' => [
                'q' => $search,
                'status' => $status ?? '',
            ],
            'status_options' => collect(ApplicationStatus::cases())
                ->map(fn (ApplicationStatus $case) => [
                    'value' => $case->value,
                    'label' => $case->toCandidateStatusLabel(),
                ])
                ->values()
                ->all(),
        ]);
    }

    public function candidates(Request $request): Response
    {
        $profile = $this->profile($request);

        $stageKeys = implode(',', array_column(EmployerApplicationPipeline::talentPoolStages(), 'key'));

        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'stage' => ['nullable', 'string', 'in:'.$stageKeys],
        ]);

        $search = trim((string) ($validated['q'] ?? ''));
        $stage = trim((string) ($validated['stage'] ?? ''));
        if ($stage === 'all') {
            $stage = '';
        }

        $query = $profile->applications()
            ->with(['user.candidateProfile', 'job']);

        if ($search !== '') {
            $like = '%'.$search.'%';
            $query->where(function ($builder) use ($like) {
                $builder->where('full_name', 'like', $like)
                    ->orWhereHas('user', fn ($user) => $user->where('name', 'like', $like));
            });
        }

        EmployerApplicationPipeline::applyTalentPoolStageFilter($query, $stage);

        $applications = $query
            ->latest('applications.id')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Application $application) => EmployerVisibleCandidate::fromApplication($application));

        return Inertia::render('Account/Hiring/TalentPool/Index', [
            'company_name' => $profile->displayCompany(),
            'applications' => $applications,
            'pipeline_summary' => EmployerApplicationPipeline::summaryForEmployer($profile),
            'filters' => [
                'q' => $search,
                'stage' => $stage,
            ],
        ]);
    }

    private function profile(Request $request): EmployerProfile
    {
        $user = $request->user();
        $this->authorize('viewAny', Job::class);

        $profile = $user?->employerProfile;
        abort_unless($profile instanceof EmployerProfile, 404);

        return $profile;
    }
}
