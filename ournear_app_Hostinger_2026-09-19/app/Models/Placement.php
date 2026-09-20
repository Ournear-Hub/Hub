<?php

namespace App\Models;

use App\Enums\BusinessIdPrefix;
use App\Enums\InvoiceStatus;
use App\Enums\PlacementStatus;
use App\Models\Concerns\HasBusinessId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Placement extends Model
{
    use HasBusinessId;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'employer_submission_id',
        'employer_profile_id',
        'job_id',
        'application_id',
        'status',
        'notes',
        'expected_joining_date',
        'joining_confirmed_at',
        'joining_confirmed_by',
        'actual_joining_date',
        'joining_notes',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => PlacementStatus::class,
            'expected_joining_date' => 'date',
            'joining_confirmed_at' => 'datetime',
            'actual_joining_date' => 'date',
        ];
    }

    public static function businessIdPrefix(): BusinessIdPrefix
    {
        return BusinessIdPrefix::Placement;
    }

    /**
     * @return BelongsTo<EmployerSubmission, $this>
     */
    public function submission(): BelongsTo
    {
        return $this->belongsTo(EmployerSubmission::class, 'employer_submission_id');
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
     * @return HasOne<PlacementOffer, $this>
     */
    public function offer(): HasOne
    {
        return $this->hasOne(PlacementOffer::class);
    }

    /**
     * @return HasOne<Employee, $this>
     */
    public function employee(): HasOne
    {
        return $this->hasOne(Employee::class);
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
    public function joiningConfirmer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'joining_confirmed_by');
    }

    /**
     * @return HasMany<RecruitmentActivity, $this>
     */
    public function activities(): HasMany
    {
        return $this->hasMany(RecruitmentActivity::class)->orderBy('created_at');
    }

    /**
     * @return HasMany<Replacement, $this>
     */
    public function originatingReplacements(): HasMany
    {
        return $this->hasMany(Replacement::class, 'original_placement_id');
    }

    /**
     * @return HasOne<Replacement, $this>
     */
    public function resultingReplacement(): HasOne
    {
        return $this->hasOne(Replacement::class, 'replacement_placement_id');
    }

    /**
     * @return HasOne<Invoice, $this>
     */
    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class)->where('status', '!=', InvoiceStatus::Cancelled->value);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function replacementContext(): ?array
    {
        $this->loadMissing([
            'originatingReplacements.replacementEmployee.application',
            'resultingReplacement.originalEmployee.application',
            'resultingReplacement.originalPlacement',
        ]);

        $originating = $this->originatingReplacements->sortByDesc('id')->first();
        $resulting = $this->resultingReplacement;

        if ($originating === null && $resulting === null) {
            return null;
        }

        return [
            'as_original' => $originating ? [
                'id' => $originating->id,
                'business_id' => $originating->business_id,
                'status' => $originating->status->value,
                'replacement_employee_id' => $originating->replacement_employee_id,
                'replacement_employee_name' => $originating->replacementEmployee?->application?->full_name,
            ] : null,
            'as_replacement' => $resulting ? [
                'id' => $resulting->id,
                'business_id' => $resulting->business_id,
                'status' => $resulting->status->value,
                'original_employee_id' => $resulting->original_employee_id,
                'original_employee_name' => $resulting->originalEmployee?->application?->full_name,
                'original_placement_id' => $resulting->originalPlacement?->id,
                'original_placement_business_id' => $resulting->originalPlacement?->business_id,
            ] : null,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function invoiceSummary(): ?array
    {
        $this->loadMissing('invoice.payments');
        $invoice = $this->invoice;
        if ($invoice === null || $invoice->status === InvoiceStatus::Cancelled) {
            return null;
        }

        return [
            'id' => $invoice->id,
            'business_id' => $invoice->business_id,
            'status' => $invoice->status->value,
            'currency' => $invoice->currency,
            'total_amount' => (string) $invoice->total_amount,
            'outstanding_amount' => $invoice->outstandingAmount(),
            'is_overdue' => $invoice->isOverdue(),
        ];
    }

    public function canOffer(): bool
    {
        return $this->status === PlacementStatus::Open;
    }

    public function canAccept(): bool
    {
        return $this->status === PlacementStatus::Offered && $this->offer !== null;
    }

    public function canDecline(): bool
    {
        return $this->status === PlacementStatus::Offered && $this->offer !== null;
    }

    public function canConfirmJoining(): bool
    {
        return $this->status === PlacementStatus::Accepted;
    }

    public function canJoin(): bool
    {
        return $this->status === PlacementStatus::JoiningConfirmed;
    }

    public function canWithdraw(): bool
    {
        return in_array($this->status, [PlacementStatus::Open, PlacementStatus::Offered], true);
    }

    /**
     * @return array<string, mixed>
     */
    public function toAdminListArray(): array
    {
        $this->loadMissing([
            'employer.user',
            'job',
            'application.user.candidateProfile',
            'submission',
            'offer',
            'employee',
            'invoice.payments',
        ]);

        $application = $this->application;
        $profile = $application?->user?->candidateProfile;

        return [
            'id' => $this->id,
            'business_id' => $this->business_id,
            'status' => $this->status->value,
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
            'expected_joining_date' => $this->expected_joining_date?->toDateString(),
            'actual_joining_date' => $this->actual_joining_date?->toDateString(),
            'employer' => $this->employer?->toAdminSummary(),
            'job' => $this->job ? [
                'id' => $this->job->id,
                'business_id' => $this->job->business_id,
                'title' => $this->job->title,
                'slug' => $this->job->slug,
            ] : null,
            'candidate' => [
                'id' => $profile?->id,
                'business_id' => $profile?->business_id,
                'name' => $application?->full_name,
            ],
            'application' => $application ? [
                'id' => $application->id,
                'business_id' => $application->business_id,
            ] : null,
            'submission' => $this->submission ? [
                'id' => $this->submission->id,
                'business_id' => $this->submission->business_id,
                'status' => $this->submission->status->value,
            ] : null,
            'employee' => $this->employee ? [
                'id' => $this->employee->id,
                'business_id' => $this->employee->business_id,
            ] : null,
            'replacement' => $this->replacementContext(),
            'invoice' => $this->invoiceSummary(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toAdminArray(): array
    {
        $this->loadMissing([
            'employer.user',
            'employer.classifiedIndustry',
            'job',
            'application',
            'submission',
            'offer.creator:id,name',
            'employee',
            'creator:id,name',
            'joiningConfirmer:id,name',
            'activities.actor:id,name',
        ]);

        $payload = $this->toAdminListArray();
        $payload['notes'] = $this->notes;
        $payload['joining_notes'] = $this->joining_notes;
        $payload['joining_confirmed_at'] = $this->joining_confirmed_at?->toDateTimeString();
        $payload['joining_confirmed_by'] = $this->joiningConfirmer?->name;
        $payload['created_by'] = $this->creator?->name;
        $payload['can_offer'] = $this->canOffer();
        $payload['can_accept'] = $this->canAccept();
        $payload['can_decline'] = $this->canDecline();
        $payload['can_confirm_joining'] = $this->canConfirmJoining();
        $payload['can_join'] = $this->canJoin();
        $payload['can_withdraw'] = $this->canWithdraw();
        $payload['employer'] = $this->employer?->toAdminArray();
        $payload['job'] = $this->job?->toAdminArray();
        $payload['application'] = $this->application?->toAdminArray(true);
        $payload['submission'] = $this->submission?->toAdminListArray();
        $payload['offer'] = $this->offer?->toAdminArray();
        $payload['employee'] = $this->employee?->toAdminListArray();
        $payload['resume_url'] = $this->application
            ? route('admin.applications.resume', $this->application)
            : null;
        $payload['has_resume'] = $this->application
            ? filled($this->application->getRawOriginal('resume_path') ?? $this->application->getAttributes()['resume_path'] ?? null)
            : false;
        $payload['activities'] = $this->timeline();
        $payload['replacement'] = $this->replacementContext();

        return $payload;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function timeline(): array
    {
        $items = $this->activities
            ->map(fn (RecruitmentActivity $activity) => $activity->toAdminArray())
            ->values();

        if ($this->submission) {
            $items = $items->concat(
                $this->submission->activities()->with('actor:id,name')->get()
                    ->map(fn (RecruitmentActivity $activity) => $activity->toAdminArray())
            );
        }

        if ($this->application) {
            $items = $items->concat(
                $this->application->activities()->with('actor:id,name')->get()
                    ->map(fn (RecruitmentActivity $activity) => $activity->toAdminArray())
            );
        }

        return $items
            ->unique('id')
            ->sortBy('created_at')
            ->values()
            ->all();
    }
}
