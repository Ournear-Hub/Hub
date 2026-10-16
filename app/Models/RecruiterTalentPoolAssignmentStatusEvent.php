<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecruiterTalentPoolAssignmentStatusEvent extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'recruiter_talent_pool_assignment_id',
        'from_status',
        'to_status',
        'progress_stage',
        'notes',
        'actor_id',
    ];

    /**
     * @return BelongsTo<RecruiterTalentPoolAssignment, $this>
     */
    public function assignment(): BelongsTo
    {
        return $this->belongsTo(RecruiterTalentPoolAssignment::class, 'recruiter_talent_pool_assignment_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
