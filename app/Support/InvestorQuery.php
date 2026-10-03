<?php

namespace App\Support;

use App\Models\InvestmentOpportunity;
use App\Models\Investor;
use App\Models\InvestorInterest;
use App\Models\RecruitmentActivity;
use App\Models\Task;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

class InvestorQuery
{
    /**
     * @return LengthAwarePaginator<int, Investor>
     */
    public function investors(Request $request): LengthAwarePaginator
    {
        $query = Investor::query()
            ->with(['user:id,name,email,role,account_status', 'creator:id,name'])
            ->withCount('interests')
            ->latest('id');

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(function ($inner) use ($search) {
                $inner->where('display_name', 'like', '%'.$search.'%')
                    ->orWhere('contact_person', 'like', '%'.$search.'%')
                    ->orWhere('email', 'like', '%'.$search.'%')
                    ->orWhere('business_id', 'like', '%'.$search.'%');
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        return $query->paginate(20)->withQueryString();
    }

    /**
     * @return LengthAwarePaginator<int, InvestmentOpportunity>
     */
    public function opportunities(Request $request): LengthAwarePaginator
    {
        $query = InvestmentOpportunity::query()
            ->with(['creator:id,name'])
            ->withCount('interests')
            ->latest('id');

        if ($request->filled('opportunity_status')) {
            $query->where('status', $request->string('opportunity_status')->toString());
        }

        if ($request->filled('search') && $request->string('tab')->toString() === 'opportunities') {
            $search = $request->string('search')->toString();
            $query->where(function ($inner) use ($search) {
                $inner->where('title', 'like', '%'.$search.'%')
                    ->orWhere('sector', 'like', '%'.$search.'%')
                    ->orWhere('location', 'like', '%'.$search.'%')
                    ->orWhere('business_id', 'like', '%'.$search.'%');
            });
        }

        return $query->paginate(20)->withQueryString();
    }

    /**
     * @return LengthAwarePaginator<int, InvestorInterest>
     */
    public function interests(Request $request): LengthAwarePaginator
    {
        $query = InvestorInterest::query()
            ->with([
                'investor:id,business_id,display_name,status',
                'opportunity:id,business_id,title,status',
                'creator:id,name',
            ])
            ->latest('id');

        if ($request->filled('interest_status')) {
            $query->where('status', $request->string('interest_status')->toString());
        }

        if ($request->filled('investor')) {
            $query->where('investor_id', $request->integer('investor'));
        }

        if ($request->filled('opportunity')) {
            $query->where('investment_opportunity_id', $request->integer('opportunity'));
        }

        return $query->paginate(20)->withQueryString();
    }

    public function selectedInvestor(Request $request): ?Investor
    {
        $id = $request->integer('investor');
        if ($id <= 0) {
            return null;
        }

        return Investor::query()
            ->with([
                'user:id,name,email,role,account_status',
                'creator:id,name',
                'verifier:id,name',
                'interests.opportunity:id,business_id,title,status',
                'tags',
            ])
            ->find($id);
    }

    public function selectedOpportunity(Request $request): ?InvestmentOpportunity
    {
        $id = $request->integer('opportunity');
        if ($id <= 0) {
            return null;
        }

        return InvestmentOpportunity::query()
            ->with(['creator:id,name', 'interests.investor:id,business_id,display_name'])
            ->find($id);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function activityFor(?Investor $investor, ?InvestmentOpportunity $opportunity): array
    {
        $query = RecruitmentActivity::query()->with('actor:id,name')->latest('id')->limit(25);

        if ($investor) {
            $query->where('investor_id', $investor->id);
        } elseif ($opportunity) {
            $query->where('investment_opportunity_id', $opportunity->id);
        } else {
            return [];
        }

        return $query->get()->map(fn (RecruitmentActivity $item) => $item->toAdminArray())->values()->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function tasksFor(?Investor $investor, ?InvestmentOpportunity $opportunity): array
    {
        $query = Task::query()
            ->with(['assignee:id,name', 'creator:id,name'])
            ->latest('id')
            ->limit(25);

        if ($investor) {
            $query->where('investor_id', $investor->id);
        } elseif ($opportunity) {
            $query->where('investment_opportunity_id', $opportunity->id);
        } else {
            return [];
        }

        return $query->get()->map(fn (Task $task) => $task->toAdminArray())->values()->all();
    }

    /**
     * @return array<string, string>
     */
    public function filters(Request $request): array
    {
        return [
            'search' => $request->string('search')->toString(),
            'status' => $request->string('status')->toString(),
            'tab' => $request->string('tab', 'investors')->toString(),
            'investor' => $request->string('investor')->toString(),
            'opportunity' => $request->string('opportunity')->toString(),
            'opportunity_status' => $request->string('opportunity_status')->toString(),
            'interest_status' => $request->string('interest_status')->toString(),
        ];
    }
}
