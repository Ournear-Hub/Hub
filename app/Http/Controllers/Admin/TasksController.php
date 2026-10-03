<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PermissionName;
use App\Enums\TaskStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CancelTaskRequest;
use App\Http\Requests\Admin\CompleteTaskRequest;
use App\Http\Requests\Admin\StoreTaskRequest;
use App\Http\Requests\Admin\UpdateTaskRequest;
use App\Models\Task;
use App\Services\TaskService;
use App\Support\StaffOptions;
use App\Support\TaskQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TasksController extends Controller
{
    public function index(Request $request, TaskQuery $tasks): Response
    {
        $this->authorize('viewAny', Task::class);

        $selected = null;
        if ($request->filled('task')) {
            $selected = Task::query()->find($request->integer('task'));
            if ($selected && $request->user()?->cannot('view', $selected)) {
                $selected = null;
            }
        }

        return Inertia::render('Admin/Tasks/Index', [
            'tasks' => $tasks->adminIndex($request)
                ->through(fn (Task $task) => $task->toAdminArray()),
            'selected' => $selected?->toAdminArray(),
            'filters' => $tasks->filters($request),
            'statuses' => TaskStatus::options(),
            'staffUsers' => StaffOptions::users(),
            'can_manage' => $request->user()?->can('create', Task::class) === true,
            'can_manage_applications' => $request->user()?->hasPermission(PermissionName::ApplicationsManage->value) === true,
            'can_manage_jobs' => $request->user()?->hasPermission(PermissionName::JobsManage->value) === true,
            'can_manage_employers' => $request->user()?->hasPermission(PermissionName::EmployersManage->value) === true,
        ]);
    }

    public function store(StoreTaskRequest $request, TaskService $tasks): RedirectResponse
    {
        $tasks->create($request->validated(), $request->user());

        return back()->with('success', 'Task created.');
    }

    public function update(UpdateTaskRequest $request, Task $task, TaskService $tasks): RedirectResponse
    {
        $tasks->update($task, $request->validated(), $request->user());

        return back()->with('success', 'Task updated.');
    }

    public function complete(CompleteTaskRequest $request, Task $task, TaskService $tasks): RedirectResponse
    {
        $tasks->complete($task, $request->user());

        return back()->with('success', 'Task completed.');
    }

    public function cancel(CancelTaskRequest $request, Task $task, TaskService $tasks): RedirectResponse
    {
        $tasks->cancel($task, $request->user());

        return back()->with('success', 'Task cancelled.');
    }
}
