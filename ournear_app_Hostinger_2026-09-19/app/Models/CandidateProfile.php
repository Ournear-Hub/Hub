<?php

namespace App\Models;

use App\Enums\BusinessIdPrefix;
use App\Models\Concerns\HasBusinessId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
}
