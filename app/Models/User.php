<?php

namespace App\Models;

use App\Enums\AccountStatus;
use App\Enums\BusinessIdPrefix;
use App\Enums\PermissionName;
use App\Enums\PreferredLanguage;
use App\Enums\RegistrationSource;
use App\Enums\StaffRoleSlug;
use App\Enums\UserRole;
use App\Models\Concerns\HasBusinessId;
use App\Services\UserActivityService;
use App\Services\UserPhotoService;
use App\Services\CommunicationPreferenceService;
use App\Support\AccessCatalog;
use Database\Factories\UserFactory;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasBusinessId, HasFactory, Notifiable;

    /**
     * @var list<string>|null
     */
    private ?array $permissionCache = null;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'username',
        'mobile',
        'whatsapp_number',
        'preferred_language',
        'location',
        'bio',
        'professional_headline',
        'account_status',
        'registration_source',
        'brand_id',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'role' => 'candidate',
        'is_active' => true,
        'account_status' => 'active',
        'preferred_language' => 'en',
        'must_change_password' => false,
        'profile_completion' => 0,
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'photo_path',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'hiring_email_verified_at' => 'datetime',
            'mobile_verified_at' => 'datetime',
            'whatsapp_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'last_activity_at' => 'datetime',
            'registration_source' => RegistrationSource::class,
            'password_changed_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'account_status' => AccountStatus::class,
            'preferred_language' => PreferredLanguage::class,
            'is_active' => 'boolean',
            'must_change_password' => 'boolean',
            'profile_completion' => 'integer',
            'person_number' => 'integer',
        ];
    }

    public static function businessIdPrefix(): BusinessIdPrefix
    {
        return BusinessIdPrefix::User;
    }

    protected static function booted(): void
    {
        static::creating(function (User $user): void {
            if (! filled($user->username)) {
                $user->username = static::uniqueUsernameFromEmail((string) $user->email);
            }

            if ($user->account_status === null) {
                $user->account_status = $user->is_active ? AccountStatus::Active : AccountStatus::Inactive;
            }

            if ($user->brand_id === null && \Illuminate\Support\Facades\Schema::hasTable('brands')) {
                $user->brand_id = Brand::query()->where('is_primary', true)->value('id');
            }

            if ($user->registration_source === null && \Illuminate\Support\Facades\Schema::hasColumn('users', 'registration_source')) {
                $user->registration_source = in_array($user->role, [UserRole::Admin, UserRole::Partner, UserRole::Investor], true)
                    ? RegistrationSource::Admin
                    : RegistrationSource::Website;
            }

            app(\App\Support\OurNearHireIdentity::class)->assign($user);
        });

        static::saving(function (User $user): void {
            if ($user->exists && $user->isDirty('person_number') && filled($user->getOriginal('person_number'))) {
                $user->person_number = $user->getOriginal('person_number');
            }

            if ($user->isDirty('mobile')) {
                $original = $user->getOriginal('mobile');
                $incoming = $user->mobile;
                if (! is_string($incoming) || trim($incoming) === '') {
                    $user->mobile = null;
                } else {
                    $normalized = \App\Support\MobileNumber::normalize($incoming);
                    if (is_string($normalized)) {
                        $user->mobile = $normalized;
                    }
                }

                if ($user->exists && ! \App\Support\MobileNumber::same(
                    is_string($original) ? $original : null,
                    $user->mobile,
                )) {
                    $user->mobile_verified_at = null;
                }
            }

            if (\Illuminate\Support\Facades\Schema::hasColumn('users', 'whatsapp_number') && $user->isDirty('whatsapp_number')) {
                $original = $user->getOriginal('whatsapp_number');
                $incoming = $user->whatsapp_number;
                if (! is_string($incoming) || trim($incoming) === '') {
                    $user->whatsapp_number = null;
                } else {
                    $normalized = \App\Support\UserWhatsAppNumber::normalize($incoming);
                    if (is_string($normalized)) {
                        $user->whatsapp_number = $normalized;
                    }
                }

                if ($user->exists && ! \App\Support\UserWhatsAppNumber::same(
                    is_string($original) ? $original : null,
                    $user->whatsapp_number,
                )) {
                    $user->whatsapp_verified_at = null;
                }
            }

            if ($user->isDirty('account_status') && $user->account_status instanceof AccountStatus) {
                $user->is_active = $user->account_status->allowsSignIn();
            } elseif ($user->isDirty('is_active')) {
                if ($user->is_active) {
                    $user->account_status = AccountStatus::Active;
                } elseif (! $user->account_status instanceof AccountStatus || $user->account_status === AccountStatus::Active) {
                    $user->account_status = AccountStatus::Inactive;
                }
            }

            $user->profile_completion = $user->calculateProfileCompletion();
        });

        static::created(function (User $user): void {
            if (\Illuminate\Support\Facades\Schema::hasTable('user_communication_preferences')) {
                app(CommunicationPreferenceService::class)->ensureDefaults($user);
            }

            if ($user->isAdmin() && \Illuminate\Support\Facades\Schema::hasTable('admin_profiles')) {
                $user->adminProfile()->firstOrCreate(
                    ['user_id' => $user->id],
                    ['joined_at' => optional($user->created_at)?->toDateString()],
                );
            }

            if (\Illuminate\Support\Facades\Schema::hasTable('user_activities')) {
                app(UserActivityService::class)->record(
                    $user,
                    \App\Enums\UserActivityType::Registered,
                    'Registered on OurNear.',
                    null,
                    ['source' => 'user_created'],
                );
            }

            $user->forceFill(['profile_completion' => $user->calculateProfileCompletion()])->saveQuietly();
        });
    }

    public static function uniqueUsernameFromEmail(string $email): string
    {
        $base = Str::slug((string) Str::before($email, '@')) ?: 'user';
        $candidate = $base;
        $i = 1;

        while (static::query()->where('username', $candidate)->exists()) {
            $candidate = $base.$i;
            $i++;
        }

        return Str::limit($candidate, 64, '');
    }

    public static function normalizeUsername(?string $username): string
    {
        return Str::lower(trim((string) $username));
    }

    public static function usernameIsValid(string $username): bool
    {
        return (bool) preg_match('/^[a-z0-9_-]{3,64}$/', $username);
    }

    /**
     * @return list<mixed>
     */
    public static function usernameRules(?int $ignoreUserId = null): array
    {
        $unique = \Illuminate\Validation\Rule::unique('users', 'username');
        if ($ignoreUserId !== null) {
            $unique = $unique->ignore($ignoreUserId);
        }

        return ['required', 'string', 'min:3', 'max:64', 'alpha_dash', $unique];
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function isCandidate(): bool
    {
        return $this->role === UserRole::Candidate;
    }

    public function isEmployer(): bool
    {
        return $this->role === UserRole::Employer;
    }

    public function isPartner(): bool
    {
        return $this->role === UserRole::Partner;
    }

    public function isInvestor(): bool
    {
        return $this->role === UserRole::Investor;
    }

    public function isAccountMember(): bool
    {
        return $this->isCandidate() || $this->isEmployer();
    }

    public function accountStatus(): AccountStatus
    {
        if ($this->account_status instanceof AccountStatus) {
            return $this->account_status;
        }

        return $this->is_active ? AccountStatus::Active : AccountStatus::Inactive;
    }

    public function isActive(): bool
    {
        return $this->accountStatus()->allowsSignIn() && (bool) $this->is_active;
    }

    public function canSignIn(): bool
    {
        return $this->isActive();
    }

    /**
     * Account-type gate for the Admin panel. Staff RBAC is a separate layer.
     */
    public function canAccessAdminPanel(): bool
    {
        return $this->isAdmin() && $this->isActive();
    }

    public function canManageRecruitment(): bool
    {
        return $this->hasAnyPermission(AccessCatalog::recruitmentManagePermissions());
    }

    public function canManageWebsite(): bool
    {
        return $this->hasPermission(PermissionName::WebsiteManage->value);
    }

    public function canViewOperationalAnalytics(): bool
    {
        return $this->hasAnyPermission(AccessCatalog::operationalAnalyticsPermissions());
    }

    public function canViewHiringCommandCenter(): bool
    {
        return $this->hasAnyPermission(AccessCatalog::hiringCommandCenterPermissions());
    }

    public function canManageUsers(): bool
    {
        return $this->hasPermission(PermissionName::UsersManage->value);
    }

    public function canManageMasterData(): bool
    {
        return $this->hasPermission(PermissionName::MasterDataManage->value);
    }

    public function hasStaffRole(string $slug): bool
    {
        $this->loadMissing('roles');

        return $this->roles->contains(fn (Role $role) => $role->slug === $slug);
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasStaffRole(StaffRoleSlug::SuperAdmin->value);
    }

    public function hasPermission(string $permission): bool
    {
        if (! $this->isActive() || ! $this->isAdmin()) {
            return false;
        }

        return in_array($permission, $this->permissionNames(), true);
    }

    /**
     * @param  list<string>  $permissions
     */
    public function hasAnyPermission(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->hasPermission($permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<string>
     */
    public function permissionNames(): array
    {
        if ($this->permissionCache !== null) {
            return $this->permissionCache;
        }

        $this->loadMissing('roles.permissions');

        $this->permissionCache = $this->roles
            ->flatMap(fn (Role $role) => $role->permissions->pluck('name'))
            ->unique()
            ->values()
            ->all();

        return $this->permissionCache;
    }

    public function forgetPermissionCache(): void
    {
        $this->permissionCache = null;
        $this->unsetRelation('roles');
    }

    public function calculateProfileCompletion(): int
    {
        $score = 0;

        foreach ($this->profileCompletionItems() as $item) {
            if ($item['complete']) {
                $score += $item['points'];
            }
        }

        return min(100, $score);
    }

    /**
     * Profile completion is capped at 100.
     *
     * Personal identity (all roles):
     * name 15, email 15, username 10, photo 10, location 10, mobile 10, bio 10, email_verified 10.
     *
     * Employers and staff keep attached_profile 10 for an existing EmployerProfile/AdminProfile.
     *
     * Candidates do not score points merely because a CandidateProfile row exists.
     * Their remaining 10 points are earned professional completeness:
     * current role 2, experience 2, industry/functional area 2, at least one skill 2,
     * at least one employment-history record 2.
     *
     * Optional salary, notice period, and preference fields do not change the score.
     *
     * @return list<array{key: string, label: string, complete: bool, points: int}>
     */
    public function profileCompletionItems(): array
    {
        $items = [
            ['key' => 'name', 'label' => 'Full name', 'complete' => filled($this->name), 'points' => 15],
            ['key' => 'email', 'label' => 'Email', 'complete' => filled($this->email), 'points' => 15],
            ['key' => 'username', 'label' => 'Username', 'complete' => filled($this->username), 'points' => 10],
            ['key' => 'photo', 'label' => 'Profile photo', 'complete' => filled($this->photo_path), 'points' => 10],
            ['key' => 'location', 'label' => 'Location', 'complete' => filled($this->location), 'points' => 10],
            ['key' => 'mobile', 'label' => 'Mobile', 'complete' => filled($this->mobile), 'points' => 10],
            ['key' => 'bio', 'label' => 'Bio', 'complete' => filled($this->bio), 'points' => 10],
            ['key' => 'email_verified', 'label' => 'Email verification', 'complete' => $this->email_verified_at !== null, 'points' => 10],
        ];

        if ($this->isCandidate()) {
            return array_merge($items, $this->candidateProfessionalCompletionItems());
        }

        $hasAttachedProfile = $this->exists && (
            $this->employerProfile()->exists()
            || $this->adminProfile()->exists()
            || (Schema::hasTable('channel_partners') && $this->channelPartner()->exists())
            || (Schema::hasTable('investors') && $this->investor()->exists())
        );

        $items[] = [
            'key' => 'attached_profile',
            'label' => 'Account profile',
            'complete' => $hasAttachedProfile,
            'points' => 10,
        ];

        return $items;
    }

    /**
     * @return list<array{key: string, label: string, complete: bool, points: int}>
     */
    private function candidateProfessionalCompletionItems(): array
    {
        $profile = null;

        if ($this->exists) {
            $profile = $this->relationLoaded('candidateProfile')
                ? $this->candidateProfile
                : $this->candidateProfile()
                    ->withCount(['skills', 'employmentHistories'])
                    ->first();
        }

        $skillCount = 0;
        $historyCount = 0;

        if ($profile instanceof CandidateProfile) {
            if (Schema::hasTable('candidate_skill')) {
                $skillCount = (int) ($profile->skills_count ?? $profile->skills()->count());
            }
            if (Schema::hasTable('candidate_employment_histories')) {
                $historyCount = (int) ($profile->employment_histories_count ?? $profile->employmentHistories()->count());
            }
        }

        return [
            [
                'key' => 'current_role',
                'label' => 'Current role',
                'complete' => filled($profile?->current_designation) || filled($profile?->current_company),
                'points' => 2,
            ],
            [
                'key' => 'experience',
                'label' => 'Experience',
                'complete' => filled($profile?->experience) || filled($profile?->experience_range_id),
                'points' => 2,
            ],
            [
                'key' => 'industry_or_function',
                'label' => 'Industry or functional area',
                'complete' => filled($profile?->industry_id) || filled($profile?->job_category_id),
                'points' => 2,
            ],
            [
                'key' => 'skills',
                'label' => 'Skills',
                'complete' => $skillCount > 0,
                'points' => 2,
            ],
            [
                'key' => 'employment_history',
                'label' => 'Employment history',
                'complete' => $historyCount > 0,
                'points' => 2,
            ],
        ];
    }

    public function sendEmailVerificationNotification(): void
    {
        if (! $this->isAccountMember() || $this->hasVerifiedEmail()) {
            return;
        }

        $this->notify(new VerifyEmail);

        if (\Illuminate\Support\Facades\Schema::hasTable('user_activities')) {
            app(UserActivityService::class)->record(
                $this,
                \App\Enums\UserActivityType::SecurityEvent,
                'Email verification was requested.',
                $this,
            );
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function toAccountProfileArray(): array
    {
        $language = $this->preferred_language instanceof PreferredLanguage
            ? $this->preferred_language->value
            : (string) ($this->preferred_language ?: 'en');
        $status = $this->accountStatus();

        $pendingEmail = Schema::hasColumn($this->getTable(), 'pending_email')
            ? (strtolower(trim((string) $this->pending_email)) ?: null)
            : null;

        return [
            'name' => $this->name,
            'username' => $this->username,
            'email' => $this->email,
            'pending_email' => $pendingEmail,
            'mobile' => $this->mobile,
            'location' => $this->location,
            'bio' => $this->bio,
            'professional_headline' => Schema::hasColumn('users', 'professional_headline')
                ? $this->professional_headline
                : null,
            'preferred_language' => $language,
            'photo_url' => $this->photoUrl(),
            'email_verified' => $this->emailVerified(),
            'mobile_verified' => $this->mobileVerified(),
            'whatsapp_number' => $this->whatsapp_number,
            'whatsapp_verified' => $this->whatsappVerified(),
            'account_status' => $status->value,
            'account_status_label' => $status->label(),
            'profile_completion' => (int) $this->profile_completion,
            'completion_items' => $this->profileCompletionItems(),
            'business_id' => $this->business_id,
        ];
    }

    public function photoUrl(): ?string
    {
        return app(UserPhotoService::class)->authorizedUrl($this->photo_path, $this);
    }

    public function emailVerified(): bool
    {
        return $this->email_verified_at !== null;
    }

    public function hiringEmail(): string
    {
        if (Schema::hasColumn($this->getTable(), 'hiring_email')) {
            $email = strtolower(trim((string) $this->hiring_email));
            if ($email !== '') {
                return $email;
            }
        }

        return strtolower((string) $this->email);
    }

    public function hiringEmailVerified(): bool
    {
        if ($this->hiringEmail() === strtolower((string) $this->email)) {
            return $this->emailVerified();
        }

        return Schema::hasColumn($this->getTable(), 'hiring_email_verified_at')
            && $this->hiring_email_verified_at !== null;
    }

    public function canAccessHiringWorkspace(): bool
    {
        return $this->isEmployer() || $this->employerProfile()->exists();
    }

    public function hiringServicesActive(): bool
    {
        return $this->emailVerified()
            && $this->mobileVerified()
            && $this->hiringEmailVerified()
            && $this->canAccessHiringWorkspace();
    }

    public function mobileVerified(): bool
    {
        return $this->mobile_verified_at !== null;
    }

    public function whatsappVerified(): bool
    {
        return $this->whatsapp_verified_at !== null;
    }

    /**
     * @return BelongsToMany<Role, $this>
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class)->withTimestamps();
    }

    /**
     * @return BelongsTo<Brand, $this>
     */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    /**
     * @return HasOne<CandidateProfile, $this>
     */
    public function candidateProfile(): HasOne
    {
        return $this->hasOne(CandidateProfile::class);
    }

    /**
     * @return HasOne<EmployerProfile, $this>
     */
    public function employerProfile(): HasOne
    {
        return $this->hasOne(EmployerProfile::class);
    }

    /**
     * @return HasOne<AdminProfile, $this>
     */
    public function adminProfile(): HasOne
    {
        return $this->hasOne(AdminProfile::class);
    }

    /**
     * @return HasOne<ChannelPartner, $this>
     */
    public function channelPartner(): HasOne
    {
        return $this->hasOne(ChannelPartner::class);
    }

    /**
     * @return HasOne<Investor, $this>
     */
    public function investor(): HasOne
    {
        return $this->hasOne(Investor::class);
    }

    /**
     * @return HasMany<Application, $this>
     */
    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }

    /**
     * @return HasMany<SavedJob, $this>
     */
    public function savedJobs(): HasMany
    {
        return $this->hasMany(SavedJob::class);
    }

    /**
     * @return BelongsToMany<Job, $this>
     */
    public function savedJobListings(): BelongsToMany
    {
        return $this->belongsToMany(Job::class, 'saved_jobs')->withTimestamps();
    }

    /**
     * @return HasMany<EmployerEnquiry, $this>
     */
    public function employerEnquiries(): HasMany
    {
        return $this->hasMany(EmployerEnquiry::class);
    }

    /**
     * @return HasMany<UserActivity, $this>
     */
    public function activities(): HasMany
    {
        return $this->hasMany(UserActivity::class);
    }

    /**
     * @return HasMany<UserCommunicationPreference, $this>
     */
    public function communicationPreferences(): HasMany
    {
        return $this->hasMany(UserCommunicationPreference::class);
    }

    /**
     * @return MorphToMany<Tag, $this>
     */
    public function tags(): MorphToMany
    {
        return $this->morphToMany(Tag::class, 'taggable')->withTimestamps();
    }

    /**
     * @return HasMany<LoginLog, $this>
     */
    public function loginLogs(): HasMany
    {
        return $this->hasMany(LoginLog::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function toAdminListArray(): array
    {
        $this->loadMissing(['roles', 'brand', 'tags']);

        $status = $this->accountStatus();
        $source = $this->registration_source instanceof RegistrationSource
            ? $this->registration_source
            : RegistrationSource::tryFrom((string) $this->registration_source);

        return [
            'id' => $this->id,
            'business_id' => $this->business_id,
            'username' => $this->username,
            'name' => $this->name,
            'email' => $this->email,
            'mobile' => $this->mobile,
            'photo_url' => $this->photoUrl(),
            'account_type' => $this->role instanceof UserRole ? $this->role->value : (string) $this->role,
            'account_status' => $status->value,
            'account_status_label' => $status->label(),
            'registration_source' => $source?->value,
            'registration_source_label' => $source?->label(),
            'is_active' => $this->isActive(),
            'email_verified' => $this->emailVerified(),
            'mobile_verified' => $this->mobileVerified(),
            'preferred_language' => $this->preferred_language instanceof PreferredLanguage
                ? $this->preferred_language->value
                : (string) ($this->preferred_language ?: 'en'),
            'location' => $this->location,
            'profile_completion' => (int) $this->profile_completion,
            'last_login_at' => optional($this->last_login_at)?->toDateTimeString(),
            'last_activity_at' => optional($this->last_activity_at)?->toDateTimeString(),
            'tags' => $this->tags
                ->map(fn (Tag $tag) => $tag->toAdminArray())
                ->values()
                ->all(),
            'must_change_password' => (bool) $this->must_change_password,
            'brand' => $this->brand?->toAdminArray(),
            'roles' => $this->roles
                ->map(fn (Role $role) => [
                    'id' => $role->id,
                    'name' => $role->name,
                    'slug' => $role->slug,
                ])
                ->values()
                ->all(),
            'created_at' => optional($this->created_at)?->toDateTimeString(),
            'updated_at' => optional($this->updated_at)?->toDateTimeString(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toAdminArray(): array
    {
        $this->loadMissing('adminProfile');

        return [
            ...$this->toAdminListArray(),
            'permissions' => $this->permissionNames(),
            'is_super_admin' => $this->isSuperAdmin(),
            'admin_profile' => $this->adminProfile?->toAdminArray(),
        ];
    }
}
