<?php

namespace App\Models;

use App\Enums\BusinessIdPrefix;
use App\Enums\PaymentMethod;
use App\Models\Concerns\HasBusinessId;
use App\Support\RecruitmentWorkspace;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payment extends Model
{
    use HasBusinessId;

    public const STATUS_RECORDED = 'Recorded';

    public const GATEWAY_MESSAGE = 'Gateway integration not configured';

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
     * V1.1 stores administrator-recorded receipts. There is no payment status column
     * and no gateway verification workflow.
     */
    public function operationalStatus(): string
    {
        return self::STATUS_RECORDED;
    }

    public function isGatewayVerified(): bool
    {
        return false;
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
     * @return HasMany<RecruitmentActivity, $this>
     */
    public function activities(): HasMany
    {
        return $this->hasMany(RecruitmentActivity::class)->orderBy('created_at');
    }

    /**
     * @return array<string, mixed>
     */
    public function toAdminArray(): array
    {
        $this->loadMissing([
            'recorder:id,name',
            'invoice.application.user.candidateProfile',
            'invoice.placement',
            'invoice.job',
            'invoice.employee.application',
            'employer.user',
        ]);

        $invoice = $this->invoice;
        $application = $invoice?->application;
        $candidate = $invoice?->candidateSummary();

        return [
            'id' => $this->id,
            'business_id' => $this->business_id,
            'invoice_id' => $this->invoice_id,
            'invoice_business_id' => $invoice?->business_id,
            'invoice_status' => $invoice?->status?->value,
            'currency' => $this->currency,
            'amount' => (string) $this->amount,
            'status' => $this->operationalStatus(),
            'status_note' => 'Administrator-recorded. Not gateway-verified.',
            'gateway_configured' => false,
            'gateway_message' => self::GATEWAY_MESSAGE,
            'gateway_reference' => null,
            'paid_at' => $this->paid_at?->toDateString(),
            'created_at' => $this->created_at?->toDateTimeString(),
            'method' => $this->method->value,
            'reference' => $this->reference,
            'notes' => $this->notes,
            'recorded_by' => $this->recorder?->name,
            'candidate' => $candidate,
            'application' => $application ? [
                'id' => $application->id,
                'business_id' => $application->business_id,
            ] : null,
            'placement' => $invoice?->placement ? [
                'id' => $invoice->placement->id,
                'business_id' => $invoice->placement->business_id,
                'status' => $invoice->placement->status->value,
            ] : null,
            'job' => $invoice?->job ? [
                'id' => $invoice->job->id,
                'business_id' => $invoice->job->business_id,
                'title' => $invoice->job->title,
                'slug' => $invoice->job->slug,
            ] : null,
            'employer' => $this->employer?->toAdminSummary(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toAdminDetailArray(): array
    {
        $this->loadMissing([
            'invoice.application.user.candidateProfile',
            'invoice.submission',
            'invoice.placement',
            'invoice.job',
            'invoice.employer.user',
            'invoice.employee.application',
            'invoice.replacement',
            'employer.user',
            'recorder:id,name',
            'activities.actor:id,name',
        ]);

        $payload = $this->toAdminArray();
        $payload['chain'] = RecruitmentWorkspace::paymentChain($this);
        $payload['activities'] = $this->activities
            ->map(fn (RecruitmentActivity $activity) => $activity->toAdminArray())
            ->values()
            ->all();

        return $payload;
    }
}
