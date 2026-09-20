<?php

namespace App\Models;

use App\Enums\BusinessIdPrefix;
use App\Enums\PaymentMethod;
use App\Models\Concerns\HasBusinessId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasBusinessId;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'invoice_id',
        'employer_profile_id',
        'currency',
        'amount',
        'paid_at',
        'method',
        'reference',
        'notes',
        'recorded_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_at' => 'date',
            'method' => PaymentMethod::class,
        ];
    }

    public static function businessIdPrefix(): BusinessIdPrefix
    {
        return BusinessIdPrefix::Payment;
    }

    /**
     * @return BelongsTo<Invoice, $this>
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * @return BelongsTo<EmployerProfile, $this>
     */
    public function employer(): BelongsTo
    {
        return $this->belongsTo(EmployerProfile::class, 'employer_profile_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    /**
     * @return array<string, mixed>
     */
    public function toAdminArray(): array
    {
        $this->loadMissing(['recorder:id,name', 'invoice', 'employer.user']);

        return [
            'id' => $this->id,
            'business_id' => $this->business_id,
            'invoice_id' => $this->invoice_id,
            'invoice_business_id' => $this->invoice?->business_id,
            'currency' => $this->currency,
            'amount' => (string) $this->amount,
            'paid_at' => $this->paid_at?->toDateString(),
            'method' => $this->method->value,
            'reference' => $this->reference,
            'notes' => $this->notes,
            'recorded_by' => $this->recorder?->name,
            'employer' => $this->employer?->toAdminSummary(),
        ];
    }
}
