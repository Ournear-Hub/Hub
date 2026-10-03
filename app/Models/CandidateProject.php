<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CandidateProject extends Model
{
    protected $table = 'candidate_projects';
    /**
     * @var list<string>
     */
    protected $fillable = [
        'candidate_profile_id',
        'name',
        'role',
        'company',
        'description',
        'started_on',
        'ended_on',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'started_on' => 'date',
            'ended_on' => 'date',
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
     * @return array<string, mixed>
     */
    public function toAccountArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'role' => $this->role,
            'company' => $this->company,
            'description' => $this->description,
            'started_on' => $this->started_on?->toDateString(),
            'ended_on' => $this->ended_on?->toDateString(),
        ];
    }
}
