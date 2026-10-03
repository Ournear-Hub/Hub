<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InterviewScorecard extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'application_interview_id',
        'scorecard_template_id',
        'qualitative',
        'numeric_score',
        'notes',
        'submitted_by',
        'submitted_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'qualitative' => 'array',
            'numeric_score' => 'decimal:2',
            'submitted_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<ApplicationInterview, $this>
     */
    public function interview(): BelongsTo
    {
        return $this->belongsTo(ApplicationInterview::class, 'application_interview_id');
    }

    /**
     * @return BelongsTo<ScorecardTemplate, $this>
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(ScorecardTemplate::class, 'scorecard_template_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    /**
     * @return array<string, mixed>
     */
    public function toAdminArray(): array
    {
        $this->loadMissing(['template', 'submitter:id,name']);

        return [
            'id' => $this->id,
            'application_interview_id' => $this->application_interview_id,
            'scorecard_template_id' => $this->scorecard_template_id,
            'template_name' => $this->template?->name,
            'qualitative' => is_array($this->qualitative) ? $this->qualitative : [],
            'numeric_score' => $this->numeric_score !== null ? (float) $this->numeric_score : null,
            'notes' => $this->notes,
            'submitted_by' => $this->submitter?->name,
            'submitted_at' => $this->submitted_at?->toDateTimeString(),
        ];
    }
}
