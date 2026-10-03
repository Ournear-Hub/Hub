<?php

namespace App\Models;

use App\Enums\BusinessIdPrefix;
use App\Enums\PartnerAgreementStatus;
use App\Models\Concerns\HasBusinessId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChannelPartnerAgreement extends Model
{
    use HasBusinessId;

    public const DEFAULT_COMMISSION_PERCENT = '30.00';

    public const DEFAULT_DURATION_MONTHS = 3;

    public const ELIGIBLE_BASIS_RECEIVED_PAYMENTS = 'received_payments';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'channel_partner_id',
        'status',
        'commission_percent',
        'duration_months',
        'eligible_basis',
        'starts_at',
        'ends_at',
        'terms',
        'approved_at',
        'approved_by',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => PartnerAgreementStatus::class,
            'commission_percent' => 'decimal:2',
            'duration_months' => 'integer',
            'starts_at' => 'date',
            'ends_at' => 'date',
            'approved_at' => 'datetime',
        ];
    }

    public static function businessIdPrefix(): BusinessIdPrefix
    {
        return BusinessIdPrefix::PartnerAgreement;
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
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * @return HasMany<CommissionEntry, $this>
     */
    public function commissionEntries(): HasMany
    {
        return $this->hasMany(CommissionEntry::class);
    }

    public function coversDate(?string $date): bool
    {
        if (! $this->status instanceof PartnerAgreementStatus || ! $this->status->isActive()) {
            return false;
        }

        $date = $date ?: now()->toDateString();
        $start = $this->starts_at?->toDateString()
            ?? $this->approved_at?->toDateString()
            ?? $this->created_at?->toDateString();
        $end = $this->ends_at?->toDateString();

        if ($start && $date < $start) {
            return false;
        }

        if ($end && $date > $end) {
            return false;
        }

        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function toAdminArray(): array
    {
        $this->loadMissing([
            'partner:id,business_id,display_name,status',
            'creator:id,name',
            'approver:id,name',
        ]);

        return [
            'id' => $this->id,
            'business_id' => $this->business_id,
            'channel_partner_id' => $this->channel_partner_id,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'commission_percent' => (string) $this->commission_percent,
            'duration_months' => (int) $this->duration_months,
            'eligible_basis' => $this->eligible_basis,
            'starts_at' => $this->starts_at?->toDateString(),
            'ends_at' => $this->ends_at?->toDateString(),
            'terms' => $this->terms,
            'approved_at' => $this->approved_at?->toDateTimeString(),
            'creator_name' => $this->creator?->name,
            'approver_name' => $this->approver?->name,
            'partner' => $this->partner ? [
                'id' => $this->partner->id,
                'business_id' => $this->partner->business_id,
                'display_name' => $this->partner->display_name,
                'workspace_url' => route('admin.partners.index', ['partner' => $this->partner->id], false),
            ] : null,
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
