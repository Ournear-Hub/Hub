<?php

namespace App\Support;

use App\Enums\AccountStatus;
use App\Enums\PreferredLanguage;
use App\Enums\RegistrationSource;
use App\Enums\StaffRoleSlug;
use App\Enums\UserRole;
use App\Enums\UserSegmentSlug;
use App\Models\Brand;
use App\Models\Role;
use App\Models\Tag;
use App\Models\User;
use App\Models\UserSegment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class UserQuery
{
    /**
     * @return LengthAwarePaginator<int, User>
     */
    public function adminIndex(Request $request): LengthAwarePaginator
    {
        $query = User::query()
            ->with(['roles', 'brand', 'tags'])
            ->latest('id');

        $this->applySegment($query, $this->segment($request));
        $this->applySearch($query, trim((string) $request->query('search', '')));
        $this->applyFilters($query, $request);

        return $query->paginate(15)->withQueryString();
    }

    /**
     * @return LengthAwarePaginator<int, User>
     */
    public function staffIndex(Request $request): LengthAwarePaginator
    {
        $query = User::query()
            ->with(['roles', 'brand', 'adminProfile'])
            ->where('role', UserRole::Admin->value)
            ->latest('id');

        $this->applySearch($query, trim((string) $request->query('search', '')));

        $status = trim((string) $request->query('status', ''));
        if ($status !== '') {
            $this->applyAccountStatus($query, $status);
        }

        $roleId = $request->integer('role_id');
        if ($roleId > 0) {
            $query->whereHas('roles', fn (Builder $role) => $role->where('roles.id', $roleId));
        }

        return $query->paginate(15)->withQueryString();
    }

    public function selected(Request $request): ?User
    {
        $id = $request->integer('user');
        if ($id <= 0) {
            return null;
        }

        return User::query()->with(['roles.permissions', 'brand', 'adminProfile'])->find($id);
    }

    public function segment(Request $request): string
    {
        $segment = trim((string) $request->query('segment', UserSegmentSlug::All->value));

        return UserSegmentSlug::tryFrom($segment)?->value ?? UserSegmentSlug::All->value;
    }

    /**
     * @return array<string, mixed>
     */
    public function filters(Request $request): array
    {
        $selectedId = $request->integer('user');

        return [
            'search' => $request->string('search')->toString(),
            'account_type' => $request->string('account_type')->toString(),
            'status' => $request->string('status')->toString(),
            'verification' => $request->string('verification')->toString(),
            'location' => $request->string('location')->toString(),
            'language' => $request->string('language')->toString(),
            'registered_from' => $request->string('registered_from')->toString(),
            'registered_to' => $request->string('registered_to')->toString(),
            'last_login_from' => $request->string('last_login_from')->toString(),
            'last_login_to' => $request->string('last_login_to')->toString(),
            'registration_source' => $request->string('registration_source')->toString(),
            'tag_id' => $request->integer('tag_id') > 0 ? (string) $request->integer('tag_id') : '',
            'last_activity_from' => $request->string('last_activity_from')->toString(),
            'last_activity_to' => $request->string('last_activity_to')->toString(),
            'completion_min' => $request->string('completion_min')->toString(),
            'brand_id' => $request->integer('brand_id') > 0 ? (string) $request->integer('brand_id') : '',
            'role_id' => $request->integer('role_id') > 0 ? (string) $request->integer('role_id') : '',
            'segment' => $this->segment($request),
            'user' => $selectedId > 0 ? (string) $selectedId : '',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function options(): array
    {
        $segments = UserSegment::query()->orderBy('sort_order')->get();
        if ($segments->isEmpty()) {
            $segments = collect(UserSegmentSlug::cases())->map(fn (UserSegmentSlug $segment) => (object) [
                'slug' => $segment->value,
                'name' => $segment->label(),
                'description' => $segment->description(),
                'is_navigation' => in_array($segment, UserSegmentSlug::navigation(), true),
            ]);
        }

        return [
            'account_types' => array_map(fn (UserRole $role) => [
                'value' => $role->value,
                'label' => ucfirst($role->value),
            ], UserRole::cases()),
            'roles' => Role::query()
                ->orderBy('name')
                ->get(['id', 'name', 'slug', 'is_locked'])
                ->map(fn (Role $role) => [
                    'id' => $role->id,
                    'name' => $role->name,
                    'slug' => $role->slug,
                    'is_locked' => $role->is_locked,
                    'assignable' => $role->slug !== StaffRoleSlug::SuperAdmin->value,
                ])
                ->all(),
            'statuses' => array_map(fn (AccountStatus $status) => [
                'value' => $status->value,
                'label' => $status->label(),
            ], AccountStatus::cases()),
            'languages' => array_map(fn (PreferredLanguage $language) => [
                'value' => $language->value,
                'label' => $language->label(),
            ], PreferredLanguage::cases()),
            'brands' => Brand::query()->orderBy('name')->get(['id', 'name'])->map(fn (Brand $brand) => [
                'id' => $brand->id,
                'name' => $brand->name,
            ])->all(),
            'segments' => $segments->map(fn ($segment) => [
                'slug' => $segment->slug,
                'name' => $segment->name,
                'description' => $segment->description,
                'is_navigation' => (bool) $segment->is_navigation,
            ])->all(),
            'verifications' => [
                ['value' => 'email_verified', 'label' => 'Email verified'],
                ['value' => 'email_unverified', 'label' => 'Email unverified'],
                ['value' => 'mobile_verified', 'label' => 'Mobile verified'],
                ['value' => 'mobile_unverified', 'label' => 'Mobile unverified'],
            ],
            'registration_sources' => RegistrationSource::options(),
            'tags' => \Illuminate\Support\Facades\Schema::hasTable('tags')
                ? Tag::query()
                    ->orderBy('name')
                    ->get()
                    ->map(fn (Tag $tag) => $tag->toAdminArray())
                    ->all()
                : [],
        ];
    }

    private function applySearch(Builder $query, string $search): void
    {
        if ($search === '') {
            return;
        }

        $query->where(function (Builder $inner) use ($search) {
            $inner->where('name', 'like', '%'.$search.'%')
                ->orWhere('email', 'like', '%'.$search.'%')
                ->orWhere('username', 'like', '%'.$search.'%')
                ->orWhere('mobile', 'like', '%'.$search.'%')
                ->orWhere('business_id', 'like', '%'.$search.'%');
        });
    }

    private function applyFilters(Builder $query, Request $request): void
    {
        $accountType = trim((string) $request->query('account_type', ''));
        if (in_array($accountType, array_map(fn (UserRole $role) => $role->value, UserRole::cases()), true)) {
            $query->where('role', $accountType);
        }

        $status = trim((string) $request->query('status', ''));
        if ($status !== '') {
            $this->applyAccountStatus($query, $status);
        }

        $verification = trim((string) $request->query('verification', ''));
        match ($verification) {
            'email_verified' => $query->whereNotNull('email_verified_at'),
            'email_unverified' => $query->whereNull('email_verified_at'),
            'mobile_verified' => $query->whereNotNull('mobile_verified_at'),
            'mobile_unverified' => $query->whereNull('mobile_verified_at'),
            default => null,
        };

        $location = trim((string) $request->query('location', ''));
        if ($location !== '') {
            $query->where('location', 'like', '%'.$location.'%');
        }

        $language = trim((string) $request->query('language', ''));
        if (PreferredLanguage::tryFrom($language)) {
            $query->where('preferred_language', $language);
        }

        $registeredFrom = trim((string) $request->query('registered_from', ''));
        if ($registeredFrom !== '') {
            $query->whereDate('created_at', '>=', $registeredFrom);
        }

        $registeredTo = trim((string) $request->query('registered_to', ''));
        if ($registeredTo !== '') {
            $query->whereDate('created_at', '<=', $registeredTo);
        }

        $lastLoginFrom = trim((string) $request->query('last_login_from', ''));
        if ($lastLoginFrom !== '') {
            $query->whereDate('last_login_at', '>=', $lastLoginFrom);
        }

        $lastLoginTo = trim((string) $request->query('last_login_to', ''));
        if ($lastLoginTo !== '') {
            $query->whereDate('last_login_at', '<=', $lastLoginTo);
        }

        $source = trim((string) $request->query('registration_source', ''));
        if (RegistrationSource::tryFrom($source)) {
            $query->where('registration_source', $source);
        }

        $tagId = $request->integer('tag_id');
        if ($tagId > 0) {
            $query->whereHas('tags', fn (Builder $tag) => $tag->where('tags.id', $tagId));
        }

        $activityFrom = trim((string) $request->query('last_activity_from', ''));
        if ($activityFrom !== '') {
            $query->whereDate('last_activity_at', '>=', $activityFrom);
        }

        $activityTo = trim((string) $request->query('last_activity_to', ''));
        if ($activityTo !== '') {
            $query->whereDate('last_activity_at', '<=', $activityTo);
        }

        $completionMin = $request->integer('completion_min');
        if ($completionMin > 0) {
            $query->where('profile_completion', '>=', min(100, $completionMin));
        }

        $brandId = $request->integer('brand_id');
        if ($brandId > 0) {
            $query->where('brand_id', $brandId);
        }

        $roleId = $request->integer('role_id');
        if ($roleId > 0) {
            $query->whereHas('roles', fn (Builder $role) => $role->where('roles.id', $roleId));
        }
    }

    private function applyAccountStatus(Builder $query, string $status): void
    {
        if ($status === 'active') {
            $query->where(function (Builder $inner) {
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

        if ($status === 'inactive') {
            $query->where(function (Builder $inner) {
                $inner->where('account_status', AccountStatus::Inactive->value)
                    ->orWhere(function (Builder $legacy) {
                        $legacy->where('is_active', false)
                            ->where(function (Builder $status) {
                                $status->whereNull('account_status')
                                    ->orWhere('account_status', '')
                                    ->orWhere('account_status', AccountStatus::Inactive->value);
                            });
                    });
            });

            return;
        }

        if (AccountStatus::tryFrom($status)) {
            $query->where('account_status', $status);
        }
    }

    private function applySegment(Builder $query, string $segment): void
    {
        $slug = UserSegmentSlug::tryFrom($segment) ?? UserSegmentSlug::All;

        match ($slug) {
            UserSegmentSlug::All => null,
            UserSegmentSlug::Active => $this->applyAccountStatus($query, AccountStatus::Active->value),
            UserSegmentSlug::Inactive => $this->applyAccountStatus($query, AccountStatus::Inactive->value),
            UserSegmentSlug::Blocked => $query->where('account_status', AccountStatus::Blocked->value),
            UserSegmentSlug::Suspended => $query->where('account_status', AccountStatus::Suspended->value),
            UserSegmentSlug::Verified => $query->whereNotNull('email_verified_at'),
            UserSegmentSlug::Unverified => $query->whereNull('email_verified_at'),
            UserSegmentSlug::New => $query->where('created_at', '>=', now()->subDays(30)),
            UserSegmentSlug::Candidates, UserSegmentSlug::SeekingJobs => $query->where('role', UserRole::Candidate->value),
            UserSegmentSlug::Employers => $query->where('role', UserRole::Employer->value),
            UserSegmentSlug::Incomplete => $query->where('profile_completion', '<', 80),
            UserSegmentSlug::Hyderabad => $query->where('location', 'like', '%hyderabad%'),
            UserSegmentSlug::Mining => $query->where(function (Builder $inner) {
                $inner->whereHas('employerProfile.classifiedIndustry', fn (Builder $industry) => $industry->where('name', 'like', '%mining%'))
                    ->orWhereHas('candidateProfile', fn (Builder $candidate) => $candidate->where('qualification', 'like', '%mining%')->orWhere('experience', 'like', '%mining%'));
            }),
            UserSegmentSlug::Construction => $query->where(function (Builder $inner) {
                $inner->whereHas('employerProfile.classifiedIndustry', fn (Builder $industry) => $industry->where('name', 'like', '%construction%'))
                    ->orWhereHas('candidateProfile', fn (Builder $candidate) => $candidate->where('qualification', 'like', '%construction%')->orWhere('experience', 'like', '%construction%'));
            }),
        };
    }
}
