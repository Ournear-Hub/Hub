<?php

namespace App\Support;

use App\Enums\EmployerEnquiryStatus;
use App\Models\EmployerEnquiry;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

class EmployerEnquiryQuery
{
    /**
     * @return LengthAwarePaginator<int, EmployerEnquiry>
     */
    public function adminIndex(Request $request): LengthAwarePaginator
    {
        $query = EmployerEnquiry::query()->latest();

        $search = trim((string) $request->query('search', ''));
        if ($search !== '') {
            $query->where(function ($inner) use ($search) {
                $inner->where('company_name', 'like', '%'.$search.'%')
                    ->orWhere('contact_person', 'like', '%'.$search.'%')
                    ->orWhere('email', 'like', '%'.$search.'%')
                    ->orWhere('mobile_number', 'like', '%'.$search.'%');
            });
        }

        $status = trim((string) $request->query('status', ''));
        if ($status !== '') {
            $query->where('status', $status);
        }

        foreach (['industry', 'project_location', 'workforce_type'] as $field) {
            $value = trim((string) $request->query($field, ''));
            if ($value !== '') {
                $query->where($field, $value);
            }
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

    /**
     * @return array<string, int>
     */
    public function summary(): array
    {
        $counts = EmployerEnquiry::query()
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $summary = [
            'total' => (int) EmployerEnquiry::query()->count(),
        ];

        foreach (EmployerEnquiryStatus::cases() as $status) {
            $summary[$status->value] = (int) ($counts[$status->value] ?? 0);
        }

        return $summary;
    }

    /**
     * @return array<string, list<string>>
     */
    public function filterOptions(): array
    {
        return [
            'industries' => $this->distinct('industry'),
            'locations' => $this->distinct('project_location'),
            'workforce_types' => $this->distinct('workforce_type'),
        ];
    }

    /**
     * @return list<string>
     */
    private function distinct(string $column): array
    {
        return EmployerEnquiry::query()
            ->whereNotNull($column)
            ->where($column, '!=', '')
            ->distinct()
            ->orderBy($column)
            ->pluck($column)
            ->all();
    }
}
