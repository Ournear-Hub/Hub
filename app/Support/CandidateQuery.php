<?php

namespace App\Support;

use App\Enums\AccountStatus;
use App\Enums\NoticePeriod;
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
    public function adminIndex(Request $request, int $perPage = 20): LengthAwarePaginator
    {
        $query = CandidateProfile::query()
            ->select('candidate_profiles.*')
            ->join('users', 'users.id', '=', 'candidate_profiles.user_id')
            ->where('users.role', UserRole::Candidate->value)
            ->with([
                'user.tags',
                'classifiedLocation',
                'classifiedExperienceRange',
                'classifiedQualification',
                'industry',
                'jobCategory',
                'skills',
            ])
            ->withCount('applications');

        $this->applyFilters($query, $request);

        $sort = (string) $request->query('sort', 'created_at');
        $direction = strtolower((string) $request->query('direction', 'desc')) === 'asc' ? 'asc' : 'desc';

        match ($sort) {
            'name' => $query->orderBy('users.name', $direction),
            'email' => $query->orderBy('users.email', $direction),
            'location' => $query
                ->leftJoin('locations as candidate_locations', 'candidate_locations.id', '=', 'candidate_profiles.location_id')
                ->orderByRaw('COALESCE(candidate_locations.name, candidate_profiles.location) '.$direction),
            'experience' => $query
                ->leftJoin('experience_ranges as candidate_experience_ranges', 'candidate_experience_ranges.id', '=', 'candidate_profiles.experience_range_id')
                ->orderByRaw('COALESCE(candidate_experience_ranges.name, candidate_profiles.experience) '.$direction),
            'qualification' => $query
                ->leftJoin('qualifications as candidate_qualifications', 'candidate_qualifications.id', '=', 'candidate_profiles.qualification_id')
                ->orderByRaw('COALESCE(candidate_qualifications.name, candidate_profiles.qualification) '.$direction),
            'account_status' => $query->orderBy('users.status', $direction),
            default => $query->orderBy('candidate_profiles.created_at', $direction),
        };

        $query->orderBy('candidate_profiles.id', 'desc');

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * @param  Builder<CandidateProfile>  $query
     */
    private function applyFilters(Builder $query, Request $request): void
    {
        $search = trim((string) $request->query('search', ''));
        if ($search !== '') {
            $query->where(function (Builder $builder) use ($search) {
                $like = '%'.$search.'%';
                $builder
                    ->where('users.name', 'like', $like)
                    ->orWhere('users.email', 'like', $like)
                    ->orWhere('candidate_profiles.mobile', 'like', $like)
                    ->orWhere('users.mobile', 'like', $like)
                    ->orWhere('candidate_profiles.business_id', 'like', $like)
                    ->orWhere('users.business_id', 'like', $like);
            });
        }

        $jobId = $request->integer('job_id');
        if ($jobId > 0) {
            $query->whereHas('applications', fn (Builder $application) => $application->where('job_id', $jobId));
        }

        $status = trim((string) $request->query('status', ''));
        if ($status !== '' && AccountStatus::tryFrom($status)) {
            $query->where('users.status', $status);
        }

        $verification = trim((string) $request->query('verification', ''));
        if ($verification === 'verified') {
            $query->whereNotNull('users.email_verified_at');
        } elseif ($verification === 'unverified') {
            $query->whereNull('users.email_verified_at');
        }

        $this->applyMasterOrLegacyFilter($query, $request, 'location_id', 'location', Location::class);
        $this->applyMasterOrLegacyFilter($query, $request, 'experience_range_id', 'experience', ExperienceRange::class);
        $this->applyMasterOrLegacyFilter($query, $request, 'qualification_id', 'qualification', Qualification::class);

        $industryId = $request->integer('industry_id');
        if ($industryId > 0) {
            $query->where('candidate_profiles.industry_id', $industryId);
        }

        $skillId = $request->integer('skill_id');
        if ($skillId > 0) {
            $query->whereHas('skills', fn (Builder $skill) => $skill->where('skills.id', $skillId));
        }

        $tagId = $request->integer('tag_id');
        if ($tagId > 0) {
            $query->whereHas('user.tags', fn (Builder $tag) => $tag->where('tags.id', $tagId));
        }

        $notice = trim((string) $request->query('notice_period', ''));
        if ($notice !== '' && NoticePeriod::tryFrom($notice)) {
            $query->where('candidate_profiles.notice_period', $notice);
        }

        $salaryMin = trim((string) $request->query('expected_salary_min', ''));
        if ($salaryMin !== '' && is_numeric($salaryMin)) {
            $query->where('candidate_profiles.expected_salary', '>=', $salaryMin);
        }

        $salaryMax = trim((string) $request->query('expected_salary_max', ''));
        if ($salaryMax !== '' && is_numeric($salaryMax)) {
            $query->where('candidate_profiles.expected_salary', '<=', $salaryMax);
        }

        $relocate = trim((string) $request->query('willing_to_relocate', ''));
        if ($relocate === '1') {
            $query->where('candidate_profiles.willing_to_relocate', true);
        } elseif ($relocate === '0') {
            $query->where('candidate_profiles.willing_to_relocate', false);
        }

        $availability = trim((string) $request->query('availability', ''));
        if ($availability !== '') {
            $query->where('candidate_profiles.availability', 'like', '%'.$availability.'%');
        }

        $language = trim((string) $request->query('language', ''));
        if ($language !== '') {
            $query->whereHas('languages', fn (Builder $row) => $row->where('name', 'like', '%'.$language.'%'));
        }

        $createdFrom = $this->date($request->query('date_from'));
        if ($createdFrom !== null) {
            $query->whereDate('candidate_profiles.created_at', '>=', $createdFrom);
        }

        $createdTo = $this->date($request->query('date_to'));
        if ($createdTo !== null) {
            $query->whereDate('candidate_profiles.created_at', '<=', $createdTo);
        }
    }

    /**
     * @param  Builder<CandidateProfile>  $query
     * @param  class-string  $model
     */
    private function applyMasterOrLegacyFilter(Builder $query, Request $request, string $idKey, string $legacyColumn, string $model): void
    {
        $id = $request->integer($idKey);
        if ($id > 0) {
            $query->where('candidate_profiles.'.$idKey, $id);

            return;
        }

        $legacy = trim((string) $request->query($legacyColumn, ''));
        if ($legacy === '') {
            return;
        }

        $record = $model::query()->where('name', $legacy)->first();
        $query->where(function (Builder $builder) use ($idKey, $legacyColumn, $legacy, $record) {
            $builder->where('candidate_profiles.'.$legacyColumn, $legacy);
            if ($record) {
                $builder->orWhere('candidate_profiles.'.$idKey, $record->id);
            }
        });
    }

    private function date(mixed $value): ?string
    {
        $value = trim((string) $value);

        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? $value : null;
    }
}
