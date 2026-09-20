<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

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
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
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

    /**
     * V1 admin access. Future Recruiter / Manager roles will extend these
     * helpers without changing the current Admin middleware.
     */
    public function canAccessAdminPanel(): bool
    {
        return $this->isAdmin();
    }

    public function canManageRecruitment(): bool
    {
        return $this->isAdmin();
    }

    public function canManageWebsite(): bool
    {
        return $this->isAdmin();
    }

    public function canManageUsers(): bool
    {
        return $this->isAdmin();
    }

    public function canManageMasterData(): bool
    {
        return $this->isAdmin();
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
}
