<?php

namespace App\Http\Controllers\Admin;

use App\Enums\InvestmentOpportunityStatus;
use App\Enums\InvestorInterestStatus;
use App\Enums\InvestorStatus;
use App\Enums\PermissionName;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreInvestmentOpportunityRequest;
use App\Http\Requests\Admin\StoreInvestorInterestRequest;
use App\Http\Requests\Admin\StoreInvestorRequest;
use App\Http\Requests\Admin\TransitionInvestmentOpportunityRequest;
use App\Http\Requests\Admin\UpdateInvestmentOpportunityRequest;
use App\Http\Requests\Admin\UpdateInvestorInterestRequest;
use App\Http\Requests\Admin\UpdateInvestorRequest;
use App\Models\InvestmentOpportunity;
use App\Models\Investor;
use App\Models\InvestorInterest;
use App\Services\InvestmentOpportunityService;
use App\Services\InvestorInterestService;
use App\Services\InvestorService;
use App\Support\InvestorQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InvestorsController extends Controller
{
    public function index(Request $request, InvestorQuery $investors): Response
    {
        $this->authorize('viewAny', Investor::class);

        $selected = $investors->selectedInvestor($request);
        if ($selected && $request->user()?->cannot('view', $selected)) {
            $selected = null;
        }

        $selectedOpportunity = $investors->selectedOpportunity($request);
        if ($selectedOpportunity && $request->user()?->cannot('view', $selectedOpportunity)) {
            $selectedOpportunity = null;
        }

        return Inertia::render('Admin/Investors/Index', [
            'investors' => $investors->investors($request)->through(fn (Investor $investor) => $investor->toAdminArray()),
            'opportunities' => $investors->opportunities($request)->through(fn (InvestmentOpportunity $opportunity) => $opportunity->toAdminArray()),
            'interests' => $investors->interests($request)->through(fn (InvestorInterest $interest) => $interest->toAdminArray()),
            'selected' => $selected?->toAdminArray(),
            'selected_opportunity' => $selectedOpportunity?->toAdminArray(),
            'activity' => $investors->activityFor($selected, $selectedOpportunity),
            'assigned_work' => $investors->tasksFor($selected, $selectedOpportunity),
            'filters' => $investors->filters($request),
            'statuses' => InvestorStatus::options(),
            'opportunity_statuses' => InvestmentOpportunityStatus::options(),
            'interest_statuses' => InvestorInterestStatus::options(),
            'investor_options' => Investor::query()
                ->orderBy('display_name')
                ->get()
                ->map(fn (Investor $investor) => $investor->toSelectArray())
                ->values()
                ->all(),
            'opportunity_options' => InvestmentOpportunity::query()
                ->orderBy('title')
                ->get()
                ->map(fn (InvestmentOpportunity $opportunity) => $opportunity->toSelectArray())
                ->values()
                ->all(),
            'can_manage' => $request->user()?->hasPermission(PermissionName::InvestorsManage->value) === true,
        ]);
    }

    public function store(StoreInvestorRequest $request, InvestorService $investors): RedirectResponse
    {
        $investor = $investors->create($request->validated(), $request->user());

        return redirect()
            ->route('admin.investors.index', ['investor' => $investor->id])
            ->with('success', 'Investor created as pending verification.');
    }

    public function update(
        UpdateInvestorRequest $request,
        Investor $investor,
        InvestorService $investors,
    ): RedirectResponse {
        $investors->update($investor, $request->validated(), $request->user());

        return back()->with('success', 'Investor updated.');
    }

    public function verify(Request $request, Investor $investor, InvestorService $investors): RedirectResponse
    {
        $this->authorize('update', $investor);
        $investors->verify($investor, $request->user());

        return back()->with('success', 'Investor verified.');
    }

    public function suspend(Request $request, Investor $investor, InvestorService $investors): RedirectResponse
    {
        $this->authorize('update', $investor);
        $investors->suspend($investor, $request->user());

        return back()->with('success', 'Investor suspended.');
    }

    public function storeOpportunity(
        StoreInvestmentOpportunityRequest $request,
        InvestmentOpportunityService $opportunities,
    ): RedirectResponse {
        $opportunity = $opportunities->create($request->validated(), $request->user());

        return redirect()
            ->route('admin.investors.index', ['tab' => 'opportunities', 'opportunity' => $opportunity->id])
            ->with('success', 'Investment opportunity created.');
    }

    public function updateOpportunity(
        UpdateInvestmentOpportunityRequest $request,
        InvestmentOpportunity $investmentOpportunity,
        InvestmentOpportunityService $opportunities,
    ): RedirectResponse {
        $opportunities->update($investmentOpportunity, $request->validated(), $request->user());

        return back()->with('success', 'Investment opportunity updated.');
    }

    public function changeStage(
        TransitionInvestmentOpportunityRequest $request,
        InvestmentOpportunity $investmentOpportunity,
        InvestmentOpportunityService $opportunities,
    ): RedirectResponse {
        $opportunities->changeStage($investmentOpportunity, $request->validated('status'), $request->user());

        return back()->with('success', 'Investment opportunity stage updated.');
    }

    public function storeInterest(
        StoreInvestorInterestRequest $request,
        InvestorInterestService $interests,
    ): RedirectResponse {
        $interest = $interests->record($request->validated(), $request->user());

        return redirect()
            ->route('admin.investors.index', [
                'tab' => 'interests',
                'investor' => $interest->investor_id,
                'opportunity' => $interest->investment_opportunity_id,
            ])
            ->with('success', 'Investor interest recorded.');
    }

    public function updateInterest(
        UpdateInvestorInterestRequest $request,
        InvestorInterest $investorInterest,
        InvestorInterestService $interests,
    ): RedirectResponse {
        $interests->update($investorInterest, $request->validated(), $request->user());

        return back()->with('success', 'Investor interest updated.');
    }
}
