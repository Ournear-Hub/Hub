<?php

namespace App\Support;

use App\Models\ChannelPartner;
use App\Models\ChannelPartnerAgreement;
use App\Models\EmployerAttribution;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

class PartnerQuery
{
    /**
     * @return LengthAwarePaginator<int, ChannelPartner>
     */
    public function partners(Request $request): LengthAwarePaginator
    {
        $query = ChannelPartner::query()
            ->with(['user:id,name,email,role', 'creator:id,name', 'activeAgreement'])
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
     * @return LengthAwarePaginator<int, ChannelPartnerAgreement>
     */
    public function agreements(Request $request): LengthAwarePaginator
    {
        $query = ChannelPartnerAgreement::query()
            ->with(['partner:id,business_id,display_name,status', 'creator:id,name'])
            ->latest('id');

        if ($request->filled('agreement_status')) {
            $query->where('status', $request->string('agreement_status')->toString());
        }

        if ($request->filled('channel_partner_id')) {
            $query->where('channel_partner_id', $request->integer('channel_partner_id'));
        }

        return $query->paginate(20)->withQueryString();
    }

    /**
     * @return LengthAwarePaginator<int, EmployerAttribution>
     */
    public function attributions(Request $request): LengthAwarePaginator
    {
        $query = EmployerAttribution::query()
            ->with([
                'partner:id,business_id,display_name,status',
                'employer:id,business_id,company_name',
                'lead:id,company_name',
            ])
            ->latest('id');

        if ($request->filled('attribution_status')) {
            $query->where('status', $request->string('attribution_status')->toString());
        }

        if ($request->filled('channel_partner_id')) {
            $query->where('channel_partner_id', $request->integer('channel_partner_id'));
        }

        return $query->paginate(20)->withQueryString();
    }

    public function selectedPartner(Request $request): ?ChannelPartner
    {
        $id = $request->integer('partner');
        if ($id <= 0) {
            return null;
        }

        return ChannelPartner::query()
            ->with([
                'user:id,name,email,role,account_status',
                'agreements',
                'attributions.employer:id,business_id,company_name',
                'leads:id,company_name,channel_partner_id,employer_profile_id',
            ])
            ->find($id);
    }

    /**
     * @return array<string, string>
     */
    public function filters(Request $request): array
    {
        return [
            'search' => $request->string('search')->toString(),
            'status' => $request->string('status')->toString(),
            'tab' => $request->string('tab', 'partners')->toString(),
            'partner' => $request->string('partner')->toString(),
            'agreement' => $request->string('agreement')->toString(),
            'attribution' => $request->string('attribution')->toString(),
            'agreement_status' => $request->string('agreement_status')->toString(),
            'attribution_status' => $request->string('attribution_status')->toString(),
            'channel_partner_id' => $request->string('channel_partner_id')->toString(),
        ];
    }
}
