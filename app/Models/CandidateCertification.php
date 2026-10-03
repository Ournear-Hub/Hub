<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CandidateCertification extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'candidate_profile_id',
        'name',
        'issuer',
        'issued_on',
        'expires_on',
        'candidate_document_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'issued_on' => 'date',
            'expires_on' => 'date',
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
     * @return BelongsTo<CandidateDocument, $this>
     */
    public function document(): BelongsTo
    {
        return $this->belongsTo(CandidateDocument::class, 'candidate_document_id');
    }

    /**
     * @return array<string, mixed>
     */
    public function toAccountArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'issuer' => $this->issuer,
            'issued_on' => $this->issued_on?->toDateString(),
            'expires_on' => $this->expires_on?->toDateString(),
            'candidate_document_id' => $this->candidate_document_id,
        ];
    }
}
