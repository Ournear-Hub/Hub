<?php

namespace App\Http\Requests\Admin;

use App\Models\Task;
use App\Support\StaffOptions;
use Illuminate\Foundation\Http\FormRequest;

class UpdateTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        $task = $this->route('task');

        return $task instanceof Task
            && $this->user()?->can('update', $task) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'body' => ['nullable', 'string', 'max:5000'],
            'due_at' => ['nullable', 'date'],
            'assigned_to' => StaffOptions::assignedToRules(),
        ];
    }
}
