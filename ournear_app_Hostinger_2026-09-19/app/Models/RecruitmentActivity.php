<?php

namespace App\Models;

use App\Enums\RecruitmentActivityType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecruitmentActivity extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'application_id',
        'employer_enquiry_id',
        'type',
        'body',
        'from_status',
        'to_status',
        'actor_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => RecruitmentActivityType::class,
        ];
    }

    /**
     * @return BelongsTo<Application, $this>
     */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    /**
     * @return BelongsTo<EmployerEnquiry, $this>
     */
    public function employerEnquiry(): BelongsTo
    {
        return $this->belongsTo(EmployerEnquiry::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    /**
     * @return array<string, mixed>
     */
    public function toAdminArray(): array
    {
        $this->loadMissing('actor:id,name');

        return [
            'id' => $this->id,
            'type' => $this->type->value,
            'body' => $this->body,
            'from_status' => $this->from_status,
            'to_status' => $this->to_status,
            'actor_name' => $this->actor?->name,
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
