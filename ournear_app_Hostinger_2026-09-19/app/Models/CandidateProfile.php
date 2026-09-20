<?php

namespace App\Models;

use App\Enums\BusinessIdPrefix;
use App\Models\Concerns\HasBusinessId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
    ];

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
     * @return HasMany<Application, $this>
     */
    public function applications(): HasMany
    {
        return $this->hasMany(Application::class, 'user_id', 'user_id');
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
    public function toAdminArray(): array
    {
        $this->loadMissing([
            'user',
            'classifiedLocation',
            'classifiedExperienceRange',
            'classifiedQualification',
        ]);

        return [
            'id' => $this->id,
            'business_id' => $this->business_id,
            'name' => $this->user?->name,
            'email' => $this->user?->email,
            'mobile' => $this->mobile,
            'location' => $this->classifiedLocation?->name ?: $this->location,
            'experience' => $this->classifiedExperienceRange?->name ?: $this->experience,
            'qualification' => $this->classifiedQualification?->name ?: $this->qualification,
            'location_id' => $this->location_id,
            'experience_range_id' => $this->experience_range_id,
            'qualification_id' => $this->qualification_id,
            'applications_count' => $this->applications_count ?? $this->applications()->count(),
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
