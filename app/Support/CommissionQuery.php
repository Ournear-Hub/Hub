<?php

namespace App\Support;

use App\Models\CommissionEntry;
use App\Models\CommissionPayment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

class CommissionQuery
{
    /**
     * @return LengthAwarePaginator<int, CommissionEntry>
     */
    public function entries(Request $request): LengthAwarePaginator
    {
        $query = CommissionEntry::query()
            ->with([
                'partner:id,business_id,display_name',
                'employer:id,business_id,company_name',
                'payment:id,business_id,amount,paid_at',
                'invoice:id,business_id',
            ])
            ->latest('id');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        if ($request->filled('channel_partner_id')) {
            $query->where('channel_partner_id', $request->integer('channel_partner_id'));
        }

        return $query->paginate(20)->withQueryString();
    }

    /**
     * @return LengthAwarePaginator<int, CommissionPayment>
     */
    public function payouts(Request $request): LengthAwarePaginator
    {
        $query = CommissionPayment::query()
            ->with(['partner:id,business_id,display_name', 'recorder:id,name', 'entries'])
            ->latest('id');

        if ($request->filled('channel_partner_id')) {
            $query->where('channel_partner_id', $request->integer('channel_partner_id'));
        }

        return $query->paginate(20)->withQueryString();
    }

    public function selectedEntry(Request $request): ?CommissionEntry
    {
        $id = $request->integer('entry');
        if ($id <= 0) {
            return null;
        }

        return CommissionEntry::query()
            ->with(['partner', 'employer', 'payment', 'invoice', 'agreement', 'payout'])
            ->find($id);
    }

    public function selectedPayout(Request $request): ?CommissionPayment
    {
        $id = $request->integer('payout');
        if ($id <= 0) {
            return null;
        }

        return CommissionPayment::query()->with(['partner', 'entries', 'recorder'])->find($id);
    }

    /**
     * @return array<string, string>
     */
    public function filters(Request $request): array
    {
        return [
            'status' => $request->string('status')->toString(),
            'channel_partner_id' => $request->string('channel_partner_id')->toString(),
            'view' => $request->string('view', 'entries')->toString(),
            'entry' => $request->string('entry')->toString(),
            'payout' => $request->string('payout')->toString(),
        ];
    }
}
