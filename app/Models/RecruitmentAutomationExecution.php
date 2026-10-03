<?php

namespace App\Models;

use App\Enums\AutomationExecutionStatus;
use App\Enums\AutomationTrigger;
use App\Services\RecruitmentAutomationScreeningAdvisor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecruitmentAutomationExecution extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'recruitment_automation_id',
        'trigger',
        'status',
        'idempotency_key',
        'application_id',
        'job_id',
        'employer_profile_id',
        'interview_id',
        'employer_submission_id',
        'placement_id',
        'replacement_id',
        'task_id',
        'match_percent',
        'match_state',
        'result',
        'error_message',
        'executed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'trigger' => AutomationTrigger::class,
            'status' => AutomationExecutionStatus::class,
            'result' => 'array',
            'executed_at' => 'datetime',
        ];
    }

    public function automation(): BelongsTo
    {
        return $this->belongsTo(RecruitmentAutomation::class, 'recruitment_automation_id');
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function toAdminArray(): array
    {
        $result = is_array($this->result) ? $this->result : [];
        $match = is_array($result['match'] ?? null) ? $result['match'] : null;
        $evidence = is_array($result['evidence'] ?? null)
            ? $result['evidence']
            : app(RecruitmentAutomationScreeningAdvisor::class)->evidenceFromMatch($match);

        return [
            'id' => $this->id,
            'automation_id' => $this->recruitment_automation_id,
            'automation_name' => $this->automation?->name,
            'trigger' => $this->trigger?->value,
            'trigger_label' => $this->trigger?->label(),
            'status' => $this->status?->value,
            'status_label' => $this->status?->label(),
            'application_id' => $this->application_id,
            'job_id' => $this->job_id,
            'employer_profile_id' => $this->employer_profile_id,
            'task_id' => $this->task_id,
            'match_percent' => $this->match_percent,
            'match_state' => $this->match_state,
            'match_label' => $this->match_percent !== null ? $this->match_percent.'%' : ($this->match_state === 'not_enough_data' ? 'Not enough data' : null),
            'recommendation' => $result['recommendation'] ?? null,
            'reason' => $result['reason'] ?? null,
            'application_origin' => $result['application_origin'] ?? null,
            'auto_shortlist' => false,
            'matching_factors' => $evidence['matching_factors'] ?? [],
            'potential_gaps' => $evidence['potential_gaps'] ?? [],
            'unavailable_factors' => $evidence['unavailable_factors'] ?? [],
            'job_comparison' => $result['job_comparison'] ?? null,
            'error_message' => $this->error_message,
            'executed_at' => $this->executed_at?->toDateTimeString(),
        ];
    }
}
