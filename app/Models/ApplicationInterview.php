<?php

namespace App\Models;

use App\Enums\InterviewLifecycleStatus;
use App\Enums\InterviewMode;
use App\Enums\InterviewOutcome;
use App\Enums\InterviewType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ApplicationInterview extends Model
{
    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'sort_order' => 1,
        'lifecycle_status' => 'scheduled',
    ];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'application_id',
        'sort_order',
        'type',
        'lifecycle_status',
        'scheduled_at',
        'mode',
        'location_or_link',
        'interviewer_name',
        'interviewer_user_id',
        'outcome',
        'notes',
        'confirmed_at',
        'cancelled_at',
        'cancelled_reason',
        'rescheduled_from_id',
        'reminder_sent_at',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'reminder_sent_at' => 'datetime',
            'sort_order' => 'integer',
            'type' => InterviewType::class,
            'lifecycle_status' => InterviewLifecycleStatus::class,
            'mode' => InterviewMode::class,
            'outcome' => InterviewOutcome::class,
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
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function interviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'interviewer_user_id');
    }

    /**
     * @return BelongsTo<ApplicationInterview, $this>
     */
    public function rescheduledFrom(): BelongsTo
    {
        return $this->belongsTo(self::class, 'rescheduled_from_id');
    }

    /**
     * @return HasOne<InterviewScorecard, $this>
     */
    public function scorecard(): HasOne
    {
        return $this->hasOne(InterviewScorecard::class);
    }

    public function displayInterviewerName(): string
    {
        return $this->interviewer?->name ?: (string) $this->interviewer_name;
    }

    /**
     * @return array<string, mixed>
     */
    public function toAdminArray(): array
    {
        $this->loadMissing(['interviewer:id,name', 'scorecard.template', 'scorecard.submitter']);

        $lifecycle = $this->lifecycle_status ?? InterviewLifecycleStatus::Scheduled;

        return [
            'id' => $this->id,
            'application_id' => $this->application_id,
            'sort_order' => $this->sort_order,
            'type' => $this->type?->value,
            'type_label' => $this->type?->label(),
            'lifecycle_status' => $lifecycle->value,
            'lifecycle_label' => $lifecycle->label(),
            'scheduled_at' => $this->scheduled_at?->format('Y-m-d\TH:i'),
            'scheduled_at_label' => $this->scheduled_at?->toDateTimeString(),
            'mode' => $this->mode->value,
            'location_or_link' => $this->location_or_link,
            'interviewer_name' => $this->displayInterviewerName(),
            'interviewer_user_id' => $this->interviewer_user_id,
            'interviewer_user_name' => $this->interviewer?->name,
            'outcome' => $this->outcome->value,
            'notes' => $this->notes,
            'confirmed_at' => $this->confirmed_at?->toDateTimeString(),
            'cancelled_at' => $this->cancelled_at?->toDateTimeString(),
            'cancelled_reason' => $this->cancelled_reason,
            'rescheduled_from_id' => $this->rescheduled_from_id,
            'reminder_sent_at' => $this->reminder_sent_at?->toDateTimeString(),
            'can_confirm' => $lifecycle->canConfirm(),
            'can_reschedule' => $lifecycle->canReschedule(),
            'can_cancel' => $lifecycle->canCancel(),
            'scorecard' => $this->scorecard?->toAdminArray(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toAdminListArray(): array
    {
        $this->loadMissing(['application.job', 'interviewer:id,name']);

        $payload = $this->toAdminArray();
        $application = $this->application;

        $payload['application'] = $application ? [
            'id' => $application->id,
            'business_id' => $application->business_id,
            'full_name' => $application->full_name,
            'status' => $application->status->value,
            'workspace_url' => route('admin.applications.index', ['application' => $application->id], false),
        ] : null;
        $payload['job'] = $application?->job ? [
            'id' => $application->job->id,
            'business_id' => $application->job->business_id,
            'title' => $application->job->title,
        ] : null;

        return $payload;
    }
}
