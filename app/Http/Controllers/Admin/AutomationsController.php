<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ApplicationStatus;
use App\Enums\AutomationActionType;
use App\Enums\AutomationScope;
use App\Enums\AutomationTrigger;
use App\Enums\PermissionName;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreRecruitmentAutomationRequest;
use App\Http\Requests\Admin\UpdateRecruitmentAutomationRequest;
use App\Models\EmployerProfile;
use App\Models\Job;
use App\Models\RecruitmentAutomation;
use App\Models\RecruitmentAutomationExecution;
use App\Services\RecruitmentAutomationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AutomationsController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', RecruitmentAutomation::class);

        $selected = null;
        if ($request->filled('automation')) {
            $selected = RecruitmentAutomation::query()
                ->with(['job', 'employer', 'creator'])
                ->withCount('executions')
                ->find($request->integer('automation'));
            if ($selected && $request->user()?->cannot('view', $selected)) {
                $selected = null;
            }
        }

        $automations = RecruitmentAutomation::query()
            ->with(['job', 'employer'])
            ->withCount('executions')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        $history = $selected
            ? RecruitmentAutomationExecution::query()
                ->where('recruitment_automation_id', $selected->id)
                ->with('automation')
                ->orderByDesc('id')
                ->limit(50)
                ->get()
                ->map(fn (RecruitmentAutomationExecution $execution) => $execution->toAdminArray())
                ->all()
            : [];

        return Inertia::render('Admin/Automations/Index', [
            'automations' => $automations->through(fn (RecruitmentAutomation $automation) => $automation->toAdminArray()),
            'selected' => $selected?->toAdminArray(),
            'history' => $history,
            'triggers' => AutomationTrigger::options(),
            'scopes' => AutomationScope::options(),
            'actions' => AutomationActionType::options(),
            'statuses' => array_map(
                fn (ApplicationStatus $status) => ['value' => $status->value, 'label' => $status->value],
                ApplicationStatus::cases(),
            ),
            'job_options' => Job::query()->orderBy('title')->limit(80)->get(['id', 'title'])
                ->map(fn (Job $job) => ['id' => $job->id, 'label' => $job->title])
                ->all(),
            'employer_options' => EmployerProfile::query()->orderBy('company_name')->limit(80)->get(['id', 'company_name'])
                ->map(fn (EmployerProfile $employer) => ['id' => $employer->id, 'label' => $employer->displayCompany()])
                ->all(),
            'can_manage' => $request->user()?->hasPermission(PermissionName::AutomationManage->value) === true,
        ]);
    }

    public function store(StoreRecruitmentAutomationRequest $request, RecruitmentAutomationService $automations): RedirectResponse
    {
        $automation = $automations->create($request->validated(), $request->user());

        return redirect()
            ->route('admin.automations.index', ['automation' => $automation->id])
            ->with('success', 'Automation created.');
    }

    public function update(
        UpdateRecruitmentAutomationRequest $request,
        RecruitmentAutomation $automation,
        RecruitmentAutomationService $automations,
    ): RedirectResponse {
        $automations->update($automation, $request->validated(), $request->user());

        return back()->with('success', 'Automation updated.');
    }

    public function toggle(
        Request $request,
        RecruitmentAutomation $automation,
        RecruitmentAutomationService $automations,
    ): RedirectResponse {
        $this->authorize('update', $automation);
        $automations->setEnabled($automation, ! $automation->is_enabled, $request->user());

        return back()->with('success', $automation->fresh()?->is_enabled ? 'Automation enabled.' : 'Automation disabled.');
    }
}
