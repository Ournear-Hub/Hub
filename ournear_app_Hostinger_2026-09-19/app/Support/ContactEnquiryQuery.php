<?php

namespace App\Support;

use App\Models\ContactEnquiry;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

class ContactEnquiryQuery
{
    /**
     * @return LengthAwarePaginator<int, ContactEnquiry>
     */
    public function adminIndex(Request $request): LengthAwarePaginator
    {
        $query = ContactEnquiry::query()->latest();

        $search = trim((string) $request->query('search', ''));
        if ($search !== '') {
            $query->where(function ($inner) use ($search) {
                $inner->where('full_name', 'like', '%'.$search.'%')
                    ->orWhere('email', 'like', '%'.$search.'%')
                    ->orWhere('mobile_number', 'like', '%'.$search.'%')
                    ->orWhere('message', 'like', '%'.$search.'%');
            });
        }

        $status = trim((string) $request->query('status', ''));
        if ($status !== '') {
            $query->where('status', $status);
        }

        return $query->paginate(15)->withQueryString();
    }
}
