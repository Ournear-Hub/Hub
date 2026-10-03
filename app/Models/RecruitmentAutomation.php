<?php

namespace App\Models;

use App\Enums\AutomationActionType;
use App\Enums\AutomationScope;
use App\Enums\AutomationTrigger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RecruitmentAutomation extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'description',
        'trigger',
        'module',
        'scope',
        'job_id',
        'employer_profile_id',
        'is_enabled',
        'conditions',
        'actions',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'trigger' => AutomationTrigger::class,
            'scope' => AutomationScope::class,
            'is_enabled' => 'boolean',
            'conditions' => 'array',
            'actions' => 'array',
        ];
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class);
    }

    public function employer(): BelongsTo
    {
        return $this->belongsTo(EmployerProfile::class, 'employer_profile_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function executions(): HasMany
    {
        return $this->hasMany(RecruitmentAutomationExecution::class);
    }

    /**
     * @return list<string>
     */
    public function actionTypes(): array
    {
        $actions = $this->actions ?? [];

        return array_values(array_filter(array_map(function ($action) {
            if (is_string($action)) {
                return $action;
            }

            return is_array($action) ? ($action['type'] ?? null) : null;
        }, $actions)));
    }

    public function hasAction(AutomationActionType $type): bool
    {
        return in_array($type->value, $this->actionTypes(), true);
    }

    /**
     * @return array<string, mixed>
     */
    public function toAdminArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'trigger' => $this->trigger?->value,
            'trigger_label' => $this->trigger?->label(),
            'module' => $this->module,
            'scope' => $this->scope?->value,
            'scope_label' => $this->scope?->label(),
            'job_id' => $this->job_id,
            'job_title' => $this->job?->title,
            'employer_profile_id' => $this->employer_profile_id,
            'employer_name' => $this->employer?->displayCompany(),
            'is_enabled' => $this->is_enabled,
            'conditions' => $this->conditions ?? [],
            'actions' => $this->actionTypes(),
            'created_at' => $this->created_at?->toDateTimeString(),
            'execution_count' => $this->executions_count ?? $this->executions()->count(),
        ];
    }
}
