<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TalentPoolSubmissionStatusEvent extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'talent_pool_company_submission_id',
        'from_status',
        'to_status',
        'notes',
        'actor_id',
    ];

    /**
     * @return BelongsTo<TalentPoolCompanySubmission, $this>
     */
    public function submission(): BelongsTo
    {
        return $this->belongsTo(TalentPoolCompanySubmission::class, 'talent_pool_company_submission_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
