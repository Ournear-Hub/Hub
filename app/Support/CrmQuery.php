<?php

namespace App\Support;

use App\Models\CrmLead;
use App\Models\CrmOpportunity;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

class CrmQuery
{
    /**
     * @return LengthAwarePaginator<int, CrmLead>
     */
    public function adminIndex(Request $request): LengthAwarePaginator
    {
        $query = CrmLead::query()
            ->with([
                'assignee:id,name',
                'creator:id,name',
                'employer:id,business_id,company_name,user_id',
                'enquiry:id,company_name,email',
                'partner:id,business_id,display_name,status',
                'opportunities',
                'tags',
            ])
            ->latest('id');

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(function ($inner) use ($search) {
                $inner->where('company_name', 'like', '%'.$search.'%')
                    ->orWhere('contact_person', 'like', '%'.$search.'%')
                    ->orWhere('email', 'like', '%'.$search.'%')
                    ->orWhere('mobile', 'like', '%'.$search.'%');
            });
        }

        if ($request->filled('assigned_to')) {
            $query->where('assigned_to', $request->integer('assigned_to'));
        }

        if ($request->filled('source')) {
            $query->where('source', $request->string('source')->toString());
        }

        if ($request->filled('channel_partner_id')) {
            $query->where('channel_partner_id', $request->integer('channel_partner_id'));
        }

        if ($request->filled('status')) {
            $status = $request->string('status')->toString();
            $query->whereHas('opportunities', fn ($opportunity) => $opportunity->where('status', $status));
        }

        return $query->paginate(20)->withQueryString();
    }

    public function selectedLead(Request $request): ?CrmLead
    {
        $id = $request->integer('lead');
        if ($id <= 0) {
            return null;
        }

        return CrmLead::query()
            ->with([
                'assignee:id,name',
                'creator:id,name',
                'employer.user:id,email',
                'enquiry',
                'partner:id,business_id,display_name,status',
                'opportunities.assignee:id,name',
                'tags',
            ])
            ->find($id);
    }

    public function selectedOpportunity(Request $request): ?CrmOpportunity
    {
        $id = $request->integer('opportunity');
        if ($id <= 0) {
            return null;
        }

        return CrmOpportunity::query()
            ->with(['lead.employer', 'lead.enquiry', 'employer', 'assignee:id,name', 'creator:id,name'])
            ->find($id);
    }

    /**
     * @return array<string, string>
     */
    public function filters(Request $request): array
    {
        return [
            'search' => $request->string('search')->toString(),
            'assigned_to' => $request->string('assigned_to')->toString(),
            'source' => $request->string('source')->toString(),
            'status' => $request->string('status')->toString(),
            'lead' => $request->string('lead')->toString(),
            'opportunity' => $request->string('opportunity')->toString(),
            'channel_partner_id' => $request->string('channel_partner_id')->toString(),
        ];
    }
}
