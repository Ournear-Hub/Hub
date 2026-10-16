<?php

namespace App\Http\Middleware;

use App\Support\EmployerWorkspaceDestination;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureEmployer
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::guard('account')->user();

        if ($user === null) {
            abort(403);
        }

        if (! $user->canAccessHiringWorkspace()) {
            EmployerWorkspaceDestination::remember($request, $request->getPathInfo());

            if ($request->isMethod('GET')) {
                return redirect()->route('account.hiring', [
                    'destination' => $request->getPathInfo(),
                ]);
            }

            abort(403);
        }

        return $next($request);
    }
}
