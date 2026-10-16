<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffingSubmissionStatusEvent extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'staffing_candidate_submission_id',
        'from_status',
        'to_status',
        'notes',
        'actor_id',
    ];

    /**
     * @return BelongsTo<StaffingCandidateSubmission, $this>
     */
    public function submission(): BelongsTo
    {
        return $this->belongsTo(StaffingCandidateSubmission::class, 'staffing_candidate_submission_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
