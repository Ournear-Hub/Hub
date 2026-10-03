<?php

namespace App\Http\Requests\Admin;

use App\Models\Task;
use Illuminate\Foundation\Http\FormRequest;

class CompleteTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        $task = $this->route('task');

        return $task instanceof Task
            && $this->user()?->can('complete', $task) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }
}
