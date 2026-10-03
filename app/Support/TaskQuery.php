<?php

namespace App\Support;

use App\Enums\TaskStatus;
use App\Models\Task;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

class TaskQuery
{
    /**
     * @return LengthAwarePaginator<int, Task>
     */
    public function adminIndex(Request $request): LengthAwarePaginator
    {
        $query = Task::query()
            ->with([
                'assignee:id,name',
                'creator:id,name',
                'application:id,business_id,full_name',
                'job:id,business_id,title',
                'candidate.user:id,name',
                'employer:id,business_id,company_name',
                'enquiry:id,company_name',
                'crmLead:id,company_name',
                'crmOpportunity:id,title',
            ])
            ->latest('id');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        if ($request->filled('assigned_to')) {
            $query->where('assigned_to', $request->integer('assigned_to'));
        }

        if ($request->filled('application_id')) {
            $query->where('application_id', $request->integer('application_id'));
        }

        if ($request->filled('job_id')) {
            $query->where('job_id', $request->integer('job_id'));
        }

        if ($request->filled('employer_profile_id')) {
            $query->where('employer_profile_id', $request->integer('employer_profile_id'));
        }

        if ($request->filled('crm_lead_id')) {
            $query->where('crm_lead_id', $request->integer('crm_lead_id'));
        }

        if ($request->filled('crm_opportunity_id')) {
            $query->where('crm_opportunity_id', $request->integer('crm_opportunity_id'));
        }

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(function ($inner) use ($search) {
                $inner->where('title', 'like', '%'.$search.'%')
                    ->orWhere('body', 'like', '%'.$search.'%');
            });
        }

        $due = $request->string('due')->toString();
        if ($due === 'overdue') {
            $query->where('status', TaskStatus::Open)
                ->whereNotNull('due_at')
                ->where('due_at', '<', now());
        } elseif ($due === 'today') {
            $query->whereNotNull('due_at')
                ->whereDate('due_at', now()->toDateString());
        }

        return $query->paginate(20)->withQueryString();
    }

    /**
     * @return array<string, string>
     */
    public function filters(Request $request): array
    {
        return [
            'search' => $request->string('search')->toString(),
            'status' => $request->string('status')->toString(),
            'assigned_to' => $request->string('assigned_to')->toString(),
            'application_id' => $request->string('application_id')->toString(),
            'job_id' => $request->string('job_id')->toString(),
            'employer_profile_id' => $request->string('employer_profile_id')->toString(),
            'crm_lead_id' => $request->string('crm_lead_id')->toString(),
            'crm_opportunity_id' => $request->string('crm_opportunity_id')->toString(),
            'due' => $request->string('due')->toString(),
            'task' => $request->string('task')->toString(),
        ];
    }
}
