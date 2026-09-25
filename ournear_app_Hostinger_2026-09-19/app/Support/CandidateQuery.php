<?php

namespace App\Support;

use App\Enums\AccountStatus;
use App\Enums\UserRole;
use App\Models\CandidateProfile;
use App\Models\ExperienceRange;
use App\Models\Location;
use App\Models\Qualification;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class CandidateQuery
{
    /**
     * @return LengthAwarePaginator<int, CandidateProfile>
     */
    public function adminIndex(Request $request): LengthAwarePaginator
    {
        $query = CandidateProfile::query()
            ->with([
                'user',
                'classifiedLocation',
                'classifiedExperienceRange',
                'classifiedQualification',
                'industry',
                'jobCategory',
                'skills',
            ])
            ->withCount('applications')
            ->whereHas('user', fn ($user) => $user->where('role', UserRole::Candidate->value))
            ->latest();

        $search = trim((string) $request->query('search', ''));
        if ($search !== '') {
            $query->where(function ($inner) use ($search) {
                $inner->where('business_id', 'like', '%'.$search.'%')
                    ->orWhere('mobile', 'like', '%'.$search.'%')
                    ->orWhereHas('user', function ($user) use ($search) {
                        $user->where('name', 'like', '%'.$search.'%')
                            ->orWhere('email', 'like', '%'.$search.'%')
                            ->orWhere('business_id', 'like', '%'.$search.'%')
                            ->orWhere('location', 'like', '%'.$search.'%');
                    });
            });
        }

        $jobId = $request->integer('job_id');
        if ($jobId > 0) {
            $query->whereHas('applications', fn (Builder $application) => $application->where('job_id', $jobId));
        }

        $status = trim((string) $request->query('status', ''));
        if ($status !== '') {
            $query->whereHas('user', function (Builder $user) use ($status) {
                if ($status === AccountStatus::Active->value) {
                    $user->where(function (Builder $inner) {
                        $inner->where('account_status', AccountStatus::Active->value)
                            ->orWhere(function (Builder $legacy) {
                                $legacy->where('is_active', true)
                                    ->where(function (Builder $empty) {
                                        $empty->whereNull('account_status')->orWhere('account_status', '');
                                    });
                            });
                    });

                    return;
                }

                if (AccountStatus::tryFrom($status)) {
                    $user->where('account_status', $status);
                }
            });
        }

        $verification = trim((string) $request->query('verification', ''));
        if ($verification === 'verified') {
            $query->whereHas('user', fn (Builder $user) => $user->whereNotNull('email_verified_at'));
        } elseif ($verification === 'unverified') {
            $query->whereHas('user', fn (Builder $user) => $user->whereNull('email_verified_at'));
        }

        $this->applyMasterOrLegacyFilter($query, $request, 'location_id', 'location', Location::class);
        $this->applyMasterOrLegacyFilter($query, $request, 'experience_range_id', 'experience', ExperienceRange::class);
        $this->applyMasterOrLegacyFilter($query, $request, 'qualification_id', 'qualification', Qualification::class);

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
     * @param  \Illuminate\Database\Eloquent\Builder<CandidateProfile>  $query
     * @param  class-string  $model
     */
    private function applyMasterOrLegacyFilter($query, Request $request, string $idColumn, string $legacyColumn, string $model): void
    {
        $id = $request->integer($idColumn);
        if ($id <= 0) {
            return;
        }

        $name = $model::query()->whereKey($id)->value('name');

        $query->where(function ($inner) use ($id, $idColumn, $legacyColumn, $name) {
            $inner->where($idColumn, $id);

            if (is_string($name) && $name !== '') {
                $inner->orWhere(function ($legacy) use ($idColumn, $legacyColumn, $name) {
                    $legacy->whereNull($idColumn)->where($legacyColumn, $name);
                });
            }
        });
    }
}
