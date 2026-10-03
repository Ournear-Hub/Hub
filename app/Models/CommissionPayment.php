<?php

namespace App\Models;

use App\Enums\BusinessIdPrefix;
use App\Enums\PaymentMethod;
use App\Models\Concerns\HasBusinessId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CommissionPayment extends Model
{
    use HasBusinessId;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'channel_partner_id',
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
        return BusinessIdPrefix::CommissionPayment;
    }

    /**
     * @return BelongsTo<ChannelPartner, $this>
     */
    public function partner(): BelongsTo
    {
        return $this->belongsTo(ChannelPartner::class, 'channel_partner_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    /**
     * @return HasMany<CommissionEntry, $this>
     */
    public function entries(): HasMany
    {
        return $this->hasMany(CommissionEntry::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function toAdminArray(): array
    {
        $this->loadMissing([
            'partner:id,business_id,display_name',
            'recorder:id,name',
            'entries:id,business_id,commission_payment_id,commission_amount,status',
        ]);

        return [
            'id' => $this->id,
            'business_id' => $this->business_id,
            'channel_partner_id' => $this->channel_partner_id,
            'currency' => $this->currency,
            'amount' => (string) $this->amount,
            'paid_at' => $this->paid_at?->toDateString(),
            'method' => $this->method instanceof PaymentMethod ? $this->method->value : (string) $this->method,
            'reference' => $this->reference,
            'notes' => $this->notes,
            'recorded_by' => $this->recorder?->name,
            'partner' => $this->partner ? [
                'id' => $this->partner->id,
                'business_id' => $this->partner->business_id,
                'display_name' => $this->partner->display_name,
                'workspace_url' => route('admin.partners.index', ['partner' => $this->partner->id], false),
            ] : null,
            'entries' => $this->entries
                ->map(fn (CommissionEntry $entry) => [
                    'id' => $entry->id,
                    'business_id' => $entry->business_id,
                    'commission_amount' => (string) $entry->commission_amount,
                    'status' => $entry->status->value,
                ])
                ->values()
                ->all(),
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
