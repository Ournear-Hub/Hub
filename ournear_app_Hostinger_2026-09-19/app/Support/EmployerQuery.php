<?php

namespace App\Support;

use App\Enums\EmployerSubmissionStatus;
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
        $query = $this->baseQuery();

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

        $employerId = trim((string) $request->query('employer_id', ''));
        if ($employerId !== '') {
            $query->where(function (Builder $inner) use ($employerId) {
                $inner->where('business_id', $employerId);
                if (ctype_digit($employerId)) {
                    $inner->orWhereKey((int) $employerId);
                }
            });
        }

        $company = trim((string) $request->query('company', ''));
        if ($company !== '') {
            $query->where('company_name', 'like', '%'.$company.'%');
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

        $location = trim((string) $request->query('location', ''));
        if ($location !== '') {
            $query->whereHas('jobs', function (Builder $job) use ($location) {
                $job->where('location', 'like', '%'.$location.'%')
                    ->orWhereHas('classifiedLocation', fn (Builder $classified) => $classified->where('name', 'like', '%'.$location.'%'));
            });
        }

        $status = trim((string) $request->query('status', ''));
        if ($status !== '') {
            $query->whereHas('user', fn (Builder $user) => $user->where('account_status', $status));
        }

        $verification = trim((string) $request->query('verification', ''));
        if ($verification === 'verified') {
            $query->whereHas('user', fn (Builder $user) => $user->whereNotNull('email_verified_at'));
        } elseif ($verification === 'unverified') {
            $query->whereHas('user', fn (Builder $user) => $user->whereNull('email_verified_at'));
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

        return $this->baseQuery()->find($id);
    }

    /**
     * @return array<string, mixed>
     */
    public function filters(Request $request): array
    {
        $selectedId = $request->integer('employer');

        return [
            'search' => $request->string('search')->toString(),
            'employer_id' => $request->string('employer_id')->toString(),
            'company' => $request->string('company')->toString(),
            'industry_id' => $request->string('industry_id')->toString(),
            'location' => $request->string('location')->toString(),
            'status' => $request->string('status')->toString(),
            'verification' => $request->string('verification')->toString(),
            'date_from' => $request->string('date_from')->toString(),
            'date_to' => $request->string('date_to')->toString(),
            'employer' => $selectedId > 0 ? (string) $selectedId : '',
        ];
    }

    /**
     * @return Builder<EmployerProfile>
     */
    private function baseQuery(): Builder
    {
        return EmployerProfile::query()
            ->with(['user', 'classifiedIndustry'])
            ->withCount([
                'jobs',
                'jobs as active_jobs_count' => fn (Builder $jobs) => $jobs->where('active', true),
                'applications',
                'submissions',
                'submissions as selections_count' => fn (Builder $submissions) => $submissions
                    ->where('status', EmployerSubmissionStatus::Selected->value),
                'placements',
                'employees',
                'replacements',
            ])
            ->whereHas('user', fn ($user) => $user->where('role', UserRole::Employer->value))
            ->latest();
    }
}
