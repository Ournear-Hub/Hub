<?php

namespace App\Http\Middleware;

use App\Support\LocalFrontendAssets;
use App\Support\PublicBuildIntegrity;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Block misleading stale public/build JS when Vite dev is required (local only).
 */
class EnsureLocalFrontendDev
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! app()->environment('local')) {
            return $next($request);
        }

        // A present but unreachable public/hot forces the browser to load JS from a dead Vite URL (blank Inertia pages).
        if (is_file(public_path('hot')) && ! LocalFrontendAssets::viteDevIsReachable()) {
            LocalFrontendAssets::removeStaleHotFile();
        }

        if (! LocalFrontendAssets::publicHomeBuildIsStale()) {
            return $next($request);
        }

        if (LocalFrontendAssets::viteDevIsReachable()) {
            return $next($request);
        }

        LocalFrontendAssets::removeStaleHotFile();

        if (! $request->isMethod('GET') || ! $request->accepts(['text/html', 'application/xhtml+xml'])) {
            return $next($request);
        }

        return response()->view('errors.local-vite-required', [
            'projectPath' => base_path(),
            'homeSource' => resource_path('js/Pages/Home.tsx'),
            'missingChunks' => LocalFrontendAssets::missingPublicHomeChunks(),
            'manifestListsPublicHome' => PublicBuildIntegrity::manifestListsPublicHome(base_path()),
        ], 503);
    }
}
