<?php

namespace App\Models;

use App\Casts\RecoveredBackedEnumCast;
use App\Enums\BusinessIdPrefix;
use App\Enums\NoticePeriod;
use App\Models\Concerns\HasBusinessId;
use App\Support\CandidatePublicShare;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CandidateProfile extends Model
{
    use HasBusinessId;
    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'mobile',
        'location',
        'experience',
        'qualification',
        'location_id',
        'experience_range_id',
        'qualification_id',
        'current_designation',
        'current_company',
        'industry_id',
        'job_category_id',
        'current_salary',
        'expected_salary',
        'notice_period',
        'employment_preference',
        'willing_to_relocate',
        'availability',
        'public_share',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'current_salary' => 'decimal:2',
            'expected_salary' => 'decimal:2',
            'notice_period' => RecoveredBackedEnumCast::class.':'.NoticePeriod::class,
            'willing_to_relocate' => 'boolean',
            'public_share' => 'array',
        ];
    }

    public static function businessIdPrefix(): BusinessIdPrefix
    {
        return BusinessIdPrefix::Candidate;
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Location, $this>
     */
    public function classifiedLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'location_id');
    }

    /**
     * @return BelongsTo<ExperienceRange, $this>
     */
    public function classifiedExperienceRange(): BelongsTo
    {
        return $this->belongsTo(ExperienceRange::class, 'experience_range_id');
    }

    /**
     * @return BelongsTo<Qualification, $this>
     */
    public function classifiedQualification(): BelongsTo
    {
        return $this->belongsTo(Qualification::class, 'qualification_id');
    }

    /**
     * @return BelongsTo<Industry, $this>
     */
    public function industry(): BelongsTo
    {
        return $this->belongsTo(Industry::class);
    }

    /**
     * @return BelongsTo<JobCategory, $this>
     */
    public function jobCategory(): BelongsTo
    {
        return $this->belongsTo(JobCategory::class);
    }

    /**
     * @return BelongsToMany<Skill, $this>
     */
    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class, 'candidate_skill')->withTimestamps();
    }

    /**
     * @return HasMany<CandidateEmploymentHistory, $this>
     */
    public function employmentHistories(): HasMany
    {
        return $this->hasMany(CandidateEmploymentHistory::class)
            ->orderByDesc('is_current')
            ->orderByDesc('started_on');
    }

    /**
     * @return BelongsToMany<Location, $this>
     */
    public function preferredLocations(): BelongsToMany
    {
        return $this->belongsToMany(Location::class, 'candidate_preferred_location')->withTimestamps();
    }

    /**
     * @return BelongsToMany<EmploymentType, $this>
     */
    public function preferredEmploymentTypes(): BelongsToMany
    {
        return $this->belongsToMany(EmploymentType::class, 'candidate_preferred_employment_type')->withTimestamps();
    }

    /**
     * @return HasMany<Application, $this>
     */
    public function applications(): HasMany
    {
        return $this->hasMany(Application::class, 'user_id', 'user_id');
    }

    /**
     * @return HasMany<CandidateResume, $this>
     */
    public function resumes(): HasMany
    {
        return $this->hasMany(CandidateResume::class)
            ->orderByDesc('is_primary')
            ->orderByDesc('created_at');
    }

    /**
     * @return HasMany<CandidateDocument, $this>
     */
    public function documents(): HasMany
    {
        return $this->hasMany(CandidateDocument::class)
            ->orderByDesc('created_at');
    }

    /**
     * @return HasMany<CandidateCertification, $this>
     */
    public function certifications(): HasMany
    {
        return $this->hasMany(CandidateCertification::class)->orderBy('name');
    }

    /**
     * @return HasMany<CandidateLanguage, $this>
     */
    public function languages(): HasMany
    {
        return $this->hasMany(CandidateLanguage::class)->orderBy('name');
    }

    /**
     * @return HasMany<CandidateEducation, $this>
     */
    public function educations(): HasMany
    {
        return $this->hasMany(CandidateEducation::class)
            ->orderByDesc('ended_on')
            ->orderByDesc('started_on');
    }

    /**
     * @return HasMany<CandidateProject, $this>
     */
    public function projects(): HasMany
    {
        return $this->hasMany(CandidateProject::class)
            ->orderByDesc('ended_on')
            ->orderByDesc('started_on');
    }

    /**
     * @return array<string, bool>
     */
    public function publicShare(): array
    {
        return CandidatePublicShare::normalize($this->public_share);
    }

    public function shareAllows(string $field): bool
    {
        $share = $this->publicShare();

        if ($field === 'enabled') {
            return $share['enabled'];
        }

        return $share['enabled'] && ($share[$field] ?? false);
    }

    /**
     * @return HasMany<CandidateResume, $this>
     */
    public function primaryResume(): HasMany
    {
        return $this->hasMany(CandidateResume::class)->where('is_primary', true);
    }

    public function skillsDisplay(): ?string
    {
        $this->loadMissing('skills');

        if ($this->skills->isEmpty()) {
            return null;
        }

        return $this->skills->pluck('name')->filter()->implode(', ') ?: null;
    }

    public function formatSalary(mixed $amount): ?string
    {
        if ($amount === null || $amount === '') {
            return null;
        }

        return number_format((float) $amount, 2, '.', '');
    }

    /**
     * @return array<string, mixed>
     */
    public function toAccountArray(): array
    {
        return [
            'mobile' => $this->mobile,
            'location' => $this->location,
            'experience' => $this->experience,
            'qualification' => $this->qualification,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toProfessionalArray(): array
    {
        $this->loadMissing([
            'industry',
            'jobCategory',
            'classifiedExperienceRange',
            'classifiedQualification',
            'skills',
            'employmentHistories.industry',
            'preferredLocations',
            'preferredEmploymentTypes',
            'certifications',
            'languages',
            'educations',
            'projects',
        ]);

        $notice = $this->notice_period instanceof NoticePeriod ? $this->notice_period : null;

        return [
            'current_designation' => $this->current_designation,
            'current_company' => $this->current_company,
            'industry_id' => $this->industry_id,
            'industry' => $this->industry?->name,
            'job_category_id' => $this->job_category_id,
            'functional_area' => $this->jobCategory?->name,
            'experience' => $this->experience,
            'experience_range_id' => $this->experience_range_id,
            'qualification' => $this->qualification,
            'qualification_id' => $this->qualification_id,
            'current_salary' => $this->current_salary,
            'expected_salary' => $this->expected_salary,
            'notice_period' => $notice?->value,
            'notice_period_label' => $notice?->label(),
            'employment_preference' => $this->employment_preference,
            'skills' => $this->skills
                ->map(fn (Skill $skill) => [
                    'id' => $skill->id,
                    'name' => $skill->name,
                ])
                ->values()
                ->all(),
            'employment_histories' => $this->employmentHistories
                ->map(fn (CandidateEmploymentHistory $history) => $history->toAccountArray())
                ->values()
                ->all(),
            'preferred_location_ids' => $this->preferredLocations->pluck('id')->map(fn ($id) => (int) $id)->values()->all(),
            'preferred_locations' => $this->preferredLocations
                ->map(fn (Location $location) => [
                    'id' => $location->id,
                    'name' => $location->name,
                ])
                ->values()
                ->all(),
            'preferred_employment_type_ids' => $this->preferredEmploymentTypes->pluck('id')->map(fn ($id) => (int) $id)->values()->all(),
            'preferred_employment_types' => $this->preferredEmploymentTypes
                ->map(fn (EmploymentType $type) => [
                    'id' => $type->id,
                    'name' => $type->name,
                ])
                ->values()
                ->all(),
            'willing_to_relocate' => (bool) $this->willing_to_relocate,
            'availability' => $this->availability,
            'public_share' => $this->publicShare(),
            'certifications' => $this->certifications
                ->map(fn (CandidateCertification $certification) => $certification->toAccountArray())
                ->values()
                ->all(),
            'languages' => $this->languages
                ->map(fn (CandidateLanguage $language) => $language->toAccountArray())
                ->values()
                ->all(),
            'educations' => $this->educations
                ->map(fn (CandidateEducation $education) => $education->toAccountArray())
                ->values()
                ->all(),
            'projects' => $this->projects
                ->map(fn (CandidateProject $project) => $project->toAccountArray())
                ->values()
                ->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toAdminArray(): array
    {
        $this->loadMissing([
            'user',
            'classifiedLocation',
            'classifiedExperienceRange',
            'classifiedQualification',
            'industry',
            'jobCategory',
            'skills',
            'employmentHistories.industry',
            'preferredLocations',
            'preferredEmploymentTypes',
            'certifications',
            'languages',
            'educations',
            'projects',
            'user.tags',
        ]);

        $status = $this->user?->accountStatus();
        $notice = $this->notice_period instanceof NoticePeriod ? $this->notice_period : null;
        $histories = $this->employmentHistories
            ->map(fn (CandidateEmploymentHistory $history) => $history->toAccountArray())
            ->values()
            ->all();

        return [
            'id' => $this->id,
            'business_id' => $this->business_id,
            'ournear_id' => $this->user ? app(\App\Support\OurNearHireIdentity::class)->display($this->user, 'CAN') : null,
            'user_id' => $this->user_id,
            'user_business_id' => $this->user?->business_id,
            'profile_url' => $this->user?->username ? route('profiles.show', $this->user) : null,
            'user_360_url' => $this->user ? route('admin.users.show', $this->user, false) : null,
            'workspace_url' => route('admin.candidates.index', ['candidate' => $this->id], false),
            'name' => $this->user?->name,
            'email' => $this->user?->email,
            'mobile' => $this->mobile ?: $this->user?->mobile,
            'photo_url' => $this->user?->photoUrl(),
            'location' => $this->classifiedLocation?->name ?: $this->location,
            'experience' => $this->classifiedExperienceRange?->name ?: $this->experience,
            'qualification' => $this->classifiedQualification?->name ?: $this->qualification,
            'location_id' => $this->location_id,
            'experience_range_id' => $this->experience_range_id,
            'qualification_id' => $this->qualification_id,
            'account_status' => $status?->value,
            'account_status_label' => $status?->label(),
            'email_verified' => (bool) $this->user?->emailVerified(),
            'current_company' => $this->current_company,
            'current_designation' => $this->current_designation,
            'current_salary' => $this->formatSalary($this->current_salary),
            'expected_salary' => $this->formatSalary($this->expected_salary),
            'skills' => $this->skillsDisplay(),
            'notice_period' => $notice?->label(),
            'notice_period_value' => $notice?->value,
            'present_position' => $this->current_designation,
            'industry_id' => $this->industry_id,
            'industry' => $this->industry?->name,
            'job_category_id' => $this->job_category_id,
            'functional_area' => $this->jobCategory?->name,
            'employment_preference' => $this->employment_preference,
            'skills_list' => $this->skills
                ->map(fn (Skill $skill) => [
                    'id' => $skill->id,
                    'name' => $skill->name,
                ])
                ->values()
                ->all(),
            'employment_histories' => $histories,
            'preferred_locations' => $this->preferredLocations
                ->map(fn (Location $location) => $location->name)
                ->values()
                ->all(),
            'preferred_employment_types' => $this->preferredEmploymentTypes
                ->map(fn (EmploymentType $type) => $type->name)
                ->values()
                ->all(),
            'willing_to_relocate' => (bool) $this->willing_to_relocate,
            'availability' => $this->availability,
            'public_share' => $this->publicShare(),
            'certifications' => $this->certifications
                ->map(fn (CandidateCertification $certification) => $certification->toAccountArray())
                ->values()
                ->all(),
            'languages' => $this->languages
                ->map(fn (CandidateLanguage $language) => $language->toAccountArray())
                ->values()
                ->all(),
            'educations' => $this->educations
                ->map(fn (CandidateEducation $education) => $education->toAccountArray())
                ->values()
                ->all(),
            'projects' => $this->projects
                ->map(fn (CandidateProject $project) => $project->toAccountArray())
                ->values()
                ->all(),
            'tags' => $this->user?->tags
                ->map(fn (Tag $tag) => $tag->toAdminArray())
                ->values()
                ->all() ?? [],
            'applications_count' => $this->applications_count ?? $this->applications()->count(),
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
