<?php

namespace App\Http\Middleware;

use App\Support\MobileBrowserExperience;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BlockMobileProfileMutations
{
    /**
     * Profile and candidate professional edits are view-only on mobile browsers.
     * Job posting, staffing enquiries, and ATS actions remain allowed.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! MobileBrowserExperience::isMobileBrowser($request)) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Profile editing is available on desktop. View your profile on mobile, or switch to a desktop browser to save changes.',
            ], 403);
        }

        return redirect()
            ->back()
            ->with('error', 'Profile editing is available on desktop. View your profile on mobile, or switch to a desktop browser to save changes.');
    }
}
