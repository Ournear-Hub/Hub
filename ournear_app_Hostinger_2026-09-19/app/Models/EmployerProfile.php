<?php

namespace App\Models;

use App\Enums\BusinessIdPrefix;
use App\Models\Concerns\HasBusinessId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployerProfile extends Model
{
    use HasBusinessId;
    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'company_name',
        'contact_person',
        'mobile_number',
        'industry',
    ];

    public static function businessIdPrefix(): BusinessIdPrefix
    {
        return BusinessIdPrefix::Employer;
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Industry, $this>
     */
    public function classifiedIndustry(): BelongsTo
    {
        return $this->belongsTo(Industry::class, 'industry_id');
    }

    /**
     * @return array<string, mixed>
     */
    public function toAccountArray(): array
    {
        return [
            'company_name' => $this->company_name,
            'contact_person' => $this->contact_person,
            'mobile_number' => $this->mobile_number,
            'industry' => $this->industry,
        ];
    }
}
