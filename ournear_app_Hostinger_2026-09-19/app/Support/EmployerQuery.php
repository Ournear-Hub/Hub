<?php

namespace App\Support;

use App\Enums\UserRole;
use App\Models\EmployerProfile;
use App\Models\Industry;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class EmployerQuery
{
    /**
     * @return LengthAwarePaginator<int, EmployerProfile>
     */
    public function adminIndex(Request $request): LengthAwarePaginator
    {
        $query = EmployerProfile::query()
            ->with(['user', 'classifiedIndustry'])
            ->withCount(['jobs', 'submissions'])
            ->whereHas('user', fn ($user) => $user->where('role', UserRole::Employer->value))
            ->latest();

        $search = trim((string) $request->query('search', ''));
        if ($search !== '') {
            $query->where(function (Builder $inner) use ($search) {
                $inner->where('business_id', 'like', '%'.$search.'%')
                    ->orWhere('company_name', 'like', '%'.$search.'%')
                    ->orWhere('contact_person', 'like', '%'.$search.'%')
                    ->orWhere('mobile_number', 'like', '%'.$search.'%')
                    ->orWhereHas('user', function (Builder $user) use ($search) {
                        $user->where('name', 'like', '%'.$search.'%')
                            ->orWhere('email', 'like', '%'.$search.'%');
                    });
            });
        }

        $industryId = $request->integer('industry_id');
        if ($industryId > 0) {
            $name = Industry::query()->whereKey($industryId)->value('name');
            $query->where(function (Builder $inner) use ($industryId, $name) {
                $inner->where('industry_id', $industryId);
                if (is_string($name) && $name !== '') {
                    $inner->orWhere(function (Builder $legacy) use ($name) {
                        $legacy->whereNull('industry_id')->where('industry', $name);
                    });
                }
            });
        }

        $from = trim((string) $request->query('date_from', ''));
        if ($from !== '') {
            $query->whereDate('created_at', '>=', $from);
        }

        $to = trim((string) $request->query('date_to', ''));
        if ($to !== '') {
            $query->whereDate('created_at', '<=', $to);
        }

        return $query->paginate(15)->withQueryString();
    }

    public function selected(Request $request): ?EmployerProfile
    {
        $id = $request->integer('employer');
        if ($id <= 0) {
            return null;
        }

        return EmployerProfile::query()
            ->with(['user', 'classifiedIndustry'])
            ->withCount(['jobs', 'submissions'])
            ->find($id);
    }
}
