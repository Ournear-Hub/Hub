<?php

namespace App\Models;

use App\Enums\BusinessIdPrefix;
use App\Enums\InvoiceStatus;
use App\Models\Concerns\HasBusinessId;
use App\Support\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    use HasBusinessId;

    public const CURRENCY = 'INR';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'employer_profile_id',
        'job_id',
        'application_id',
        'employer_submission_id',
        'placement_id',
        'employee_id',
        'replacement_id',
        'status',
        'currency',
        'amount',
        'gst_rate',
        'gst_amount',
        'total_amount',
        'billed_at',
        'due_at',
        'issued_at',
        'cancelled_at',
        'notes',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => InvoiceStatus::class,
            'amount' => 'decimal:2',
            'gst_rate' => 'decimal:2',
            'gst_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'billed_at' => 'date',
            'due_at' => 'date',
            'issued_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public static function businessIdPrefix(): BusinessIdPrefix
    {
        return BusinessIdPrefix::Invoice;
    }

    /**
     * @return BelongsTo<EmployerProfile, $this>
     */
    public function employer(): BelongsTo
    {
        return $this->belongsTo(EmployerProfile::class, 'employer_profile_id');
    }

    /**
     * @return BelongsTo<Job, $this>
     */
    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class);
    }

    /**
     * @return BelongsTo<Application, $this>
     */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    /**
     * @return BelongsTo<EmployerSubmission, $this>
     */
    public function submission(): BelongsTo
    {
        return $this->belongsTo(EmployerSubmission::class, 'employer_submission_id');
    }

    /**
     * @return BelongsTo<Placement, $this>
     */
    public function placement(): BelongsTo
    {
        return $this->belongsTo(Placement::class);
    }

    /**
     * @return BelongsTo<Employee, $this>
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * @return BelongsTo<Replacement, $this>
     */
    public function replacement(): BelongsTo
    {
        return $this->belongsTo(Replacement::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<Payment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->orderBy('paid_at');
    }

    /**
     * @return HasMany<RecruitmentActivity, $this>
     */
    public function activities(): HasMany
    {
        return $this->hasMany(RecruitmentActivity::class)->orderBy('created_at');
    }

    public function receivedAmount(): string
    {
        $this->loadMissing('payments');

        return $this->payments->reduce(
            fn (string $carry, Payment $payment) => Money::add($carry, (string) $payment->amount),
            '0.00',
        );
    }

    public function outstandingAmount(): string
    {
        $outstanding = Money::sub((string) $this->total_amount, $this->receivedAmount());

        return Money::cmp($outstanding, '0') < 0 ? '0.00' : $outstanding;
    }

    public function isOverdue(): bool
    {
        return $this->status->isOpen()
            && $this->due_at !== null
            && $this->due_at->startOfDay()->lt(now()->startOfDay());
    }

    public function canIssue(): bool
    {
        return $this->status === InvoiceStatus::Draft;
    }

    public function canCancel(): bool
    {
        if ($this->status === InvoiceStatus::Draft) {
            return true;
        }

        return $this->status === InvoiceStatus::Issued && Money::cmp($this->receivedAmount(), '0') === 0;
    }

    public function canRecordPayment(): bool
    {
        return $this->status->isOpen() && Money::cmp($this->outstandingAmount(), '0') === 1;
    }

    /**
     * @return array<string, mixed>
     */
    public function toAdminListArray(): array
    {
        $this->loadMissing([
            'employer.user',
            'job',
            'employee.application',
            'payments',
        ]);

        return [
            'id' => $this->id,
            'business_id' => $this->business_id,
            'status' => $this->status->value,
            'currency' => $this->currency,
            'amount' => (string) $this->amount,
            'gst_rate' => (string) $this->gst_rate,
            'gst_amount' => (string) $this->gst_amount,
            'total_amount' => (string) $this->total_amount,
            'received_amount' => $this->receivedAmount(),
            'outstanding_amount' => $this->outstandingAmount(),
            'is_overdue' => $this->isOverdue(),
            'billed_at' => $this->billed_at?->toDateString(),
            'due_at' => $this->due_at?->toDateString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
            'employer' => $this->employer?->toAdminSummary(),
            'job' => $this->job ? [
                'id' => $this->job->id,
                'business_id' => $this->job->business_id,
                'title' => $this->job->title,
                'slug' => $this->job->slug,
            ] : null,
            'employee' => $this->employee ? [
                'id' => $this->employee->id,
                'business_id' => $this->employee->business_id,
                'name' => $this->employee->application?->full_name,
            ] : null,
            'is_replacement_billing' => $this->replacement_id !== null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toAdminArray(): array
    {
        $this->loadMissing([
            'employer.user',
            'job',
            'application',
            'submission',
            'placement',
            'employee.application',
            'replacement',
            'payments.recorder:id,name',
            'creator:id,name',
            'activities.actor:id,name',
        ]);

        $payload = $this->toAdminListArray();
        $payload['notes'] = $this->notes;
        $payload['issued_at'] = $this->issued_at?->toDateTimeString();
        $payload['cancelled_at'] = $this->cancelled_at?->toDateTimeString();
        $payload['created_by'] = $this->creator?->name;
        $payload['can_issue'] = $this->canIssue();
        $payload['can_cancel'] = $this->canCancel();
        $payload['can_record_payment'] = $this->canRecordPayment();
        $payload['application'] = $this->application ? [
            'id' => $this->application->id,
            'business_id' => $this->application->business_id,
        ] : null;
        $payload['submission'] = $this->submission ? [
            'id' => $this->submission->id,
            'business_id' => $this->submission->business_id,
        ] : null;
        $payload['placement'] = $this->placement ? [
            'id' => $this->placement->id,
            'business_id' => $this->placement->business_id,
            'status' => $this->placement->status->value,
        ] : null;
        $payload['replacement'] = $this->replacement ? [
            'id' => $this->replacement->id,
            'business_id' => $this->replacement->business_id,
            'status' => $this->replacement->status->value,
        ] : null;
        $payload['payments'] = $this->payments
            ->map(fn (Payment $payment) => $payment->toAdminArray())
            ->values()
            ->all();
        $payload['activities'] = $this->activities
            ->map(fn (RecruitmentActivity $activity) => $activity->toAdminArray())
            ->values()
            ->all();

        return $payload;
    }
}
