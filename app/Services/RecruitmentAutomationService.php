<?php

namespace App\Services;

use App\Enums\AutomationActionType;
use App\Enums\AutomationScope;
use App\Enums\AutomationTrigger;
use App\Enums\RecruitmentActivityType;
use App\Models\RecruitmentAutomation;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class RecruitmentAutomationService
{
    public function __construct(
        private RecruitmentActivityService $activities,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $actor): RecruitmentAutomation
    {
        $payload = $this->normalized($data);

        $automation = RecruitmentAutomation::query()->create([
            ...$payload,
            'created_by' => $actor->id,
        ]);

        $this->activities->record(
            actor: $actor,
            type: RecruitmentActivityType::AutomationUpdated,
            body: 'Automation created: '.$automation->name,
        );

        return $automation->fresh() ?? $automation;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(RecruitmentAutomation $automation, array $data, User $actor): RecruitmentAutomation
    {
        $automation->update($this->normalized($data, $automation));

        $this->activities->record(
            actor: $actor,
            type: RecruitmentActivityType::AutomationUpdated,
            body: 'Automation updated: '.$automation->name,
        );

        return $automation->fresh() ?? $automation;
    }

    public function setEnabled(RecruitmentAutomation $automation, bool $enabled, User $actor): RecruitmentAutomation
    {
        $automation->update(['is_enabled' => $enabled]);

        $this->activities->record(
            actor: $actor,
            type: RecruitmentActivityType::AutomationUpdated,
            body: ($enabled ? 'Automation enabled: ' : 'Automation disabled: ').$automation->name,
        );

        return $automation->fresh() ?? $automation;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalized(array $data, ?RecruitmentAutomation $existing = null): array
    {
        $trigger = $data['trigger'] instanceof AutomationTrigger
            ? $data['trigger']
            : AutomationTrigger::from((string) $data['trigger']);
        $scope = $data['scope'] instanceof AutomationScope
            ? $data['scope']
            : AutomationScope::from((string) ($data['scope'] ?? AutomationScope::Global->value));

        $actions = $this->normalizedActions($data['actions'] ?? []);
        if ($actions === []) {
            throw ValidationException::withMessages([
                'actions' => 'Choose at least one allowed action: recommend screening, create a review task, or notify staff.',
            ]);
        }

        $jobId = $scope === AutomationScope::Job ? ($data['job_id'] ?? $existing?->job_id) : null;
        $employerId = $scope === AutomationScope::Employer
            ? ($data['employer_profile_id'] ?? $existing?->employer_profile_id)
            : null;

        if ($scope === AutomationScope::Job && ! $jobId) {
            throw ValidationException::withMessages([
                'job_id' => 'A job is required for job-scoped automations.',
            ]);
        }

        if ($scope === AutomationScope::Employer && ! $employerId) {
            throw ValidationException::withMessages([
                'employer_profile_id' => 'An employer is required for employer-scoped automations.',
            ]);
        }

        $conditions = $this->normalizedConditions($data['conditions'] ?? []);

        return [
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'trigger' => $trigger->value,
            'module' => $trigger->module(),
            'scope' => $scope->value,
            'job_id' => $jobId,
            'employer_profile_id' => $employerId,
            'is_enabled' => array_key_exists('is_enabled', $data)
                ? (bool) $data['is_enabled']
                : ($existing?->is_enabled ?? true),
            'conditions' => $conditions,
            'actions' => $actions,
        ];
    }

    /**
     * @param  mixed  $actions
     * @return list<array{type: string}>
     */
    private function normalizedActions(mixed $actions): array
    {
        $values = is_array($actions) ? $actions : [];
        $types = [];

        foreach ($values as $action) {
            $type = is_array($action) ? ($action['type'] ?? null) : $action;
            if (! is_string($type) || $type === '') {
                continue;
            }

            if (in_array($type, ['shortlist', 'reject', 'select', 'offer', 'join', 'change_status', 'auto_select', 'auto_reject'], true)) {
                throw ValidationException::withMessages([
                    'actions' => 'Automation cannot shortlist, reject, select, offer, or join. Recruiter review is required.',
                ]);
            }

            $enum = AutomationActionType::tryFrom($type);
            if ($enum === null) {
                throw ValidationException::withMessages([
                    'actions' => 'Unsupported automation action.',
                ]);
            }

            $types[$enum->value] = ['type' => $enum->value];
        }

        return array_values($types);
    }

    /**
     * @param  mixed  $conditions
     * @return array<string, mixed>
     */
    private function normalizedConditions(mixed $conditions): array
    {
        $input = is_array($conditions) ? $conditions : [];
        $normalized = [];

        if (isset($input['min_match_percent']) && $input['min_match_percent'] !== '' && $input['min_match_percent'] !== null) {
            $normalized['min_match_percent'] = max(0, min(100, (int) $input['min_match_percent']));
        }

        if (isset($input['application_status']) && is_string($input['application_status']) && $input['application_status'] !== '') {
            $normalized['application_status'] = $input['application_status'];
        }

        if (isset($input['delay_hours']) && $input['delay_hours'] !== '' && $input['delay_hours'] !== null) {
            $normalized['delay_hours'] = max(1, (int) $input['delay_hours']);
        }

        if (isset($input['application_origin']) && in_array($input['application_origin'], ['guest', 'account'], true)) {
            $normalized['application_origin'] = $input['application_origin'];
        }

        if (! empty($input['allow_insufficient_data'])) {
            $normalized['allow_insufficient_data'] = true;
        }

        return $normalized;
    }
}
