<?php

namespace App\Support;

use App\Enums\FreelancerStatus;
use App\Models\Freelancer;
use App\Models\Industry;
use App\Models\Location;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

class FreelancerQuery
{
    /**
     * @return LengthAwarePaginator<int, Freelancer>
     */
    public function adminIndex(Request $request): LengthAwarePaginator
    {
        $query = Freelancer::query()
            ->with(['currentLocation', 'experienceRange'])
            ->latest('submitted_at')
            ->latest('id');

        $search = trim((string) $request->query('search', ''));
        if ($search !== '') {
            $like = '%'.$search.'%';
            $query->where(function ($inner) use ($like) {
                $inner->where('business_id', 'like', $like)
                    ->orWhere('name', 'like', $like)
                    ->orWhere('mobile', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->orWhere('primary_skill', 'like', $like);
            });
        }

        $status = trim((string) $request->query('status', ''));
        if ($status !== '') {
            $query->where('status', $status);
        }

        $industryId = (int) $request->query('industry_id', 0);
        if ($industryId > 0) {
            $query->where('industry_id', $industryId);
        }

        $locationId = (int) $request->query('location_id', 0);
        if ($locationId > 0) {
            $query->where('current_location_id', $locationId);
        }

        return $query->paginate(15)->withQueryString();
    }

    /**
     * @return array<string, mixed>
     */
    public function filterOptions(): array
    {
        return [
            'statuses' => FreelancerStatus::options(),
            'industries' => Industry::query()->active()->ordered()->get(['id', 'name'])->map(fn (Industry $item) => [
                'value' => $item->id,
                'label' => $item->name,
            ])->all(),
            'locations' => Location::query()->active()->ordered()->get(['id', 'name'])->map(fn (Location $item) => [
                'value' => $item->id,
                'label' => $item->name,
            ])->all(),
        ];
    }
}
