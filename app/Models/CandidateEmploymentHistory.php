<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CandidateEmploymentHistory extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'candidate_profile_id',
        'company',
        'designation',
        'industry_id',
        'started_on',
        'ended_on',
        'is_current',
        'is_internship',
        'description',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'started_on' => 'date',
            'ended_on' => 'date',
            'is_current' => 'boolean',
            'is_internship' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<CandidateProfile, $this>
     */
    public function candidateProfile(): BelongsTo
    {
        return $this->belongsTo(CandidateProfile::class);
    }

    /**
     * @return BelongsTo<Industry, $this>
     */
    public function industry(): BelongsTo
    {
        return $this->belongsTo(Industry::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function toAccountArray(): array
    {
        $this->loadMissing('industry');

        return [
            'id' => $this->id,
            'company' => $this->company,
            'designation' => $this->designation,
            'industry_id' => $this->industry_id,
            'industry' => $this->industry?->name,
            'started_on' => $this->started_on?->toDateString(),
            'ended_on' => $this->ended_on?->toDateString(),
            'is_current' => (bool) $this->is_current,
            'is_internship' => (bool) $this->is_internship,
            'description' => $this->description,
        ];
    }
}
