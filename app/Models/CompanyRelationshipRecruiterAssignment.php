<?php

namespace App\Models;

use App\Enums\CompanyRelationshipRecruiterAssignmentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyRelationshipRecruiterAssignment extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'employer_profile_id',
        'assigned_recruiter_user_id',
        'assigned_by_user_id',
        'status',
        'ended_at',
        'ended_by_user_id',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'status' => CompanyRelationshipRecruiterAssignmentStatus::class,
            'ended_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<EmployerProfile, $this>
     */
    public function employerProfile(): BelongsTo
    {
        return $this->belongsTo(EmployerProfile::class, 'employer_profile_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function recruiterUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_recruiter_user_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function assignedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by_user_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function endedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ended_by_user_id');
    }

    /**
     * @return array<string, mixed>
     */
    public function toAdminArray(): array
    {
        $this->loadMissing(['recruiterUser', 'assignedByUser', 'endedByUser']);

        return [
            'id' => $this->id,
            'employer_profile_id' => $this->employer_profile_id,
            'assigned_recruiter_user_id' => $this->assigned_recruiter_user_id,
            'recruiter_name' => $this->recruiterUser?->name,
            'status' => $this->status?->value,
            'status_label' => $this->status?->label(),
            'notes' => $this->notes,
            'assigned_by_name' => $this->assignedByUser?->name,
            'ended_by_name' => $this->endedByUser?->name,
            'created_at' => $this->created_at?->toDateTimeString(),
            'ended_at' => $this->ended_at?->toDateTimeString(),
        ];
    }
}
