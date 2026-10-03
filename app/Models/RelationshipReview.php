<?php

namespace App\Models;

use App\Enums\BusinessIdPrefix;
use App\Enums\RelationshipReviewDirection;
use App\Enums\RelationshipReviewStatus;
use App\Enums\RelationshipReviewVisibility;
use App\Models\Concerns\HasBusinessId;
use Database\Factories\RelationshipReviewFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RelationshipReview extends Model
{
    /** @use HasFactory<RelationshipReviewFactory> */
    use HasBusinessId, HasFactory;

    protected static function newFactory(): RelationshipReviewFactory
    {
        return RelationshipReviewFactory::new();
    }

    /**
     * @var list<string>
     */
    protected $fillable = [
        'employee_id',
        'employer_profile_id',
        'candidate_profile_id',
        'reviewer_user_id',
        'direction',
        'rating',
        'body',
        'visibility',
        'status',
        'hidden_by',
        'hidden_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'direction' => RelationshipReviewDirection::class,
            'visibility' => RelationshipReviewVisibility::class,
            'status' => RelationshipReviewStatus::class,
            'hidden_at' => 'datetime',
        ];
    }

    public static function businessIdPrefix(): BusinessIdPrefix
    {
        return BusinessIdPrefix::RelationshipReview;
    }

    /**
     * @return BelongsTo<Employee, $this>
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * @return BelongsTo<EmployerProfile, $this>
     */
    public function employerProfile(): BelongsTo
    {
        return $this->belongsTo(EmployerProfile::class);
    }

    /**
     * @return BelongsTo<CandidateProfile, $this>
     */
    public function candidateProfile(): BelongsTo
    {
        return $this->belongsTo(CandidateProfile::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_user_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function hiddenBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'hidden_by');
    }

    /**
     * @return array<string, mixed>
     */
    public function toPartyArray(): array
    {
        return [
            'id' => $this->id,
            'business_id' => $this->business_id,
            'direction' => $this->direction->value,
            'direction_label' => $this->direction->label(),
            'rating' => $this->rating,
            'body' => $this->body,
            'visibility' => $this->visibility->value,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toAdminArray(): array
    {
        $this->loadMissing(['reviewer:id,name', 'hiddenBy:id,name', 'employee']);

        return [
            ...$this->toPartyArray(),
            'employee_id' => $this->employee_id,
            'employee_business_id' => $this->employee?->business_id,
            'employer_profile_id' => $this->employer_profile_id,
            'candidate_profile_id' => $this->candidate_profile_id,
            'reviewer_user_id' => $this->reviewer_user_id,
            'reviewer_name' => $this->reviewer?->name,
            'hidden_by' => $this->hiddenBy?->name,
            'hidden_at' => $this->hidden_at?->toDateTimeString(),
        ];
    }
}
