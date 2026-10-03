<?php

namespace App\Models;

use App\Enums\BusinessIdPrefix;
use App\Enums\InvestorInterestStatus;
use App\Models\Concerns\HasBusinessId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvestorInterest extends Model
{
    use HasBusinessId;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'investor_id',
        'investment_opportunity_id',
        'status',
        'indicated_amount',
        'currency',
        'notes',
        'committed_at',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => InvestorInterestStatus::class,
            'indicated_amount' => 'decimal:2',
            'committed_at' => 'datetime',
        ];
    }

    public static function businessIdPrefix(): BusinessIdPrefix
    {
        return BusinessIdPrefix::InvestorInterest;
    }

    public function isRecordedIntent(): bool
    {
        return $this->status === InvestorInterestStatus::Committed;
    }

    /**
     * @return BelongsTo<Investor, $this>
     */
    public function investor(): BelongsTo
    {
        return $this->belongsTo(Investor::class);
    }

    /**
     * @return BelongsTo<InvestmentOpportunity, $this>
     */
    public function opportunity(): BelongsTo
    {
        return $this->belongsTo(InvestmentOpportunity::class, 'investment_opportunity_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return array<string, mixed>
     */
    public function toAdminArray(): array
    {
        $this->loadMissing([
            'investor:id,business_id,display_name,status',
            'opportunity:id,business_id,title,status',
            'creator:id,name',
        ]);

        return [
            'id' => $this->id,
            'business_id' => $this->business_id,
            'investor_id' => $this->investor_id,
            'investment_opportunity_id' => $this->investment_opportunity_id,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'indicated_amount' => $this->indicated_amount,
            'currency' => $this->currency,
            'notes' => $this->notes,
            'committed_at' => $this->committed_at?->toDateTimeString(),
            'is_recorded_intent' => $this->isRecordedIntent(),
            'created_by' => $this->created_by,
            'creator_name' => $this->creator?->name,
            'investor' => $this->investor ? [
                'id' => $this->investor->id,
                'business_id' => $this->investor->business_id,
                'display_name' => $this->investor->display_name,
                'workspace_url' => route('admin.investors.index', ['investor' => $this->investor->id], false),
            ] : null,
            'opportunity' => $this->opportunity ? [
                'id' => $this->opportunity->id,
                'business_id' => $this->opportunity->business_id,
                'title' => $this->opportunity->title,
                'workspace_url' => route('admin.investors.index', [
                    'tab' => 'opportunities',
                    'opportunity' => $this->opportunity->id,
                ], false),
            ] : null,
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
