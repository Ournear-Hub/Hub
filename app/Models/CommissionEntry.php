<?php

namespace App\Models;

use App\Enums\BusinessIdPrefix;
use App\Enums\CommissionEntryStatus;
use App\Models\Concerns\HasBusinessId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommissionEntry extends Model
{
    use HasBusinessId;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'channel_partner_id',
        'channel_partner_agreement_id',
        'employer_attribution_id',
        'payment_id',
        'invoice_id',
        'employer_profile_id',
        'commission_payment_id',
        'currency',
        'basis_amount',
        'rate',
        'commission_amount',
        'status',
        'window_starts_at',
        'window_ends_at',
        'notes',
        'approved_at',
        'approved_by',
        'voided_at',
        'voided_by',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => CommissionEntryStatus::class,
            'basis_amount' => 'decimal:2',
            'rate' => 'decimal:2',
            'commission_amount' => 'decimal:2',
            'window_starts_at' => 'date',
            'window_ends_at' => 'date',
            'approved_at' => 'datetime',
            'voided_at' => 'datetime',
        ];
    }

    public static function businessIdPrefix(): BusinessIdPrefix
    {
        return BusinessIdPrefix::CommissionEntry;
    }

    /**
     * @return BelongsTo<ChannelPartner, $this>
     */
    public function partner(): BelongsTo
    {
        return $this->belongsTo(ChannelPartner::class, 'channel_partner_id');
    }

    /**
     * @return BelongsTo<ChannelPartnerAgreement, $this>
     */
    public function agreement(): BelongsTo
    {
        return $this->belongsTo(ChannelPartnerAgreement::class, 'channel_partner_agreement_id');
    }

    /**
     * @return BelongsTo<EmployerAttribution, $this>
     */
    public function attribution(): BelongsTo
    {
        return $this->belongsTo(EmployerAttribution::class, 'employer_attribution_id');
    }

    /**
     * @return BelongsTo<Payment, $this>
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
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
     * @return BelongsTo<CommissionPayment, $this>
     */
    public function payout(): BelongsTo
    {
        return $this->belongsTo(CommissionPayment::class, 'commission_payment_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * @return array<string, mixed>
     */
    public function toAdminArray(): array
    {
        $this->loadMissing([
            'partner:id,business_id,display_name',
            'agreement:id,business_id,commission_percent,duration_months',
            'employer:id,business_id,company_name',
            'payment:id,business_id,amount,paid_at',
            'invoice:id,business_id,total_amount,status',
            'payout:id,business_id,amount,paid_at',
            'approver:id,name',
        ]);

        return [
            'id' => $this->id,
            'business_id' => $this->business_id,
            'channel_partner_id' => $this->channel_partner_id,
            'channel_partner_agreement_id' => $this->channel_partner_agreement_id,
            'employer_attribution_id' => $this->employer_attribution_id,
            'payment_id' => $this->payment_id,
            'invoice_id' => $this->invoice_id,
            'employer_profile_id' => $this->employer_profile_id,
            'commission_payment_id' => $this->commission_payment_id,
            'currency' => $this->currency,
            'basis_amount' => (string) $this->basis_amount,
            'rate' => (string) $this->rate,
            'commission_amount' => (string) $this->commission_amount,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'can_retry' => $this->status->canRetry(),
            'window_starts_at' => $this->window_starts_at?->toDateString(),
            'window_ends_at' => $this->window_ends_at?->toDateString(),
            'notes' => $this->notes,
            'approved_at' => $this->approved_at?->toDateTimeString(),
            'voided_at' => $this->voided_at?->toDateTimeString(),
            'approver_name' => $this->approver?->name,
            'partner' => $this->partner ? [
                'id' => $this->partner->id,
                'business_id' => $this->partner->business_id,
                'display_name' => $this->partner->display_name,
                'workspace_url' => route('admin.partners.index', ['partner' => $this->partner->id], false),
            ] : null,
            'employer' => $this->employer ? [
                'id' => $this->employer->id,
                'business_id' => $this->employer->business_id,
                'company_name' => $this->employer->company_name,
                'workspace_url' => route('admin.employers.index', ['employer' => $this->employer->id], false),
            ] : null,
            'payment' => $this->payment ? [
                'id' => $this->payment->id,
                'business_id' => $this->payment->business_id,
                'amount' => (string) $this->payment->amount,
                'paid_at' => $this->payment->paid_at?->toDateString(),
                'workspace_url' => route('admin.finance.index', ['view' => 'payments', 'payment' => $this->payment->id], false),
            ] : null,
            'invoice' => $this->invoice ? [
                'id' => $this->invoice->id,
                'business_id' => $this->invoice->business_id,
                'workspace_url' => route('admin.finance.index', ['invoice' => $this->invoice->id], false),
            ] : null,
            'payout' => $this->payout ? [
                'id' => $this->payout->id,
                'business_id' => $this->payout->business_id,
            ] : null,
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
