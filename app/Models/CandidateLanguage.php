<?php

namespace App\Models;

use App\Enums\LanguageProficiency;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CandidateLanguage extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'candidate_profile_id',
        'name',
        'proficiency',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'proficiency' => LanguageProficiency::class,
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
        $proficiency = $this->proficiency instanceof LanguageProficiency ? $this->proficiency : null;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'proficiency' => $proficiency?->value,
            'proficiency_label' => $proficiency?->label(),
        ];
    }
}
