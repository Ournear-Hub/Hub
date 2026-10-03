<?php

namespace App\Http\Requests;

use App\Enums\PermissionName;
use App\Models\Job;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TransitionJobLifecycleRequest extends FormRequest
{
    public function authorize(): bool
    {
        $job = $this->route('job');

        return $job instanceof Job && $this->user()?->can('update', $job) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $user = $this->user();
        $workspaceOnly = $user?->canAccessHiringWorkspace() === true
            && $user?->hasPermission(PermissionName::JobsManage->value) !== true;

        $allowed = $workspaceOnly
            ? ['publish', 'pause', 'close']
            : ['publish', 'unpublish', 'pause', 'close', 'archive'];

        return [
            'action' => ['required', 'string', Rule::in($allowed)],
        ];
    }
}
