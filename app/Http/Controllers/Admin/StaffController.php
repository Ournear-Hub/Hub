<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\UserQuery;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StaffController extends Controller
{
    public function index(Request $request, UserQuery $users): Response
    {
        $this->authorize('viewAny', User::class);

        $page = $users->staffIndex($request)
            ->through(fn (User $user) => $user->toAdminArray());

        $selected = $users->selected($request);
        if ($selected && (! $selected->isAdmin() || $request->user()?->cannot('view', $selected))) {
            $selected = null;
        }

        return Inertia::render('Admin/Staff/Index', [
            'users' => $page,
            'selected' => $selected?->toAdminArray(),
            'filters' => $users->filters($request),
            'options' => $users->options(),
            'can_manage' => $request->user()?->can('create', User::class) === true,
            'actor_is_super_admin' => $request->user()?->isSuperAdmin() === true,
        ]);
    }
}
