<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\PermissionName;
use App\Enums\StaffRoleSlug;
use App\Enums\UserRole;
use App\Support\AccessCatalog;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

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
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'role' => 'candidate',
        'is_active' => true,
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
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
            'password' => 'hashed',
            'role' => UserRole::class,
            'is_active' => 'boolean',
        ];
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

    public function isAccountMember(): bool
    {
        return $this->isCandidate() || $this->isEmployer();
    }

    public function isActive(): bool
    {
        return (bool) $this->is_active;
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

    /**
     * @return BelongsToMany<Role, $this>
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class)->withTimestamps();
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
     * @return HasMany<Application, $this>
     */
    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }

    /**
     * @return HasMany<EmployerEnquiry, $this>
     */
    public function employerEnquiries(): HasMany
    {
        return $this->hasMany(EmployerEnquiry::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function toAdminListArray(): array
    {
        $this->loadMissing('roles');

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'account_type' => $this->role instanceof UserRole ? $this->role->value : (string) $this->role,
            'is_active' => $this->isActive(),
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
        return [
            ...$this->toAdminListArray(),
            'permissions' => $this->permissionNames(),
            'is_super_admin' => $this->isSuperAdmin(),
        ];
    }
}
