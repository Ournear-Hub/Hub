<?php

namespace App\Http\Controllers;

use App\Services\PublicSocialSearchService;
use App\Support\PublicContent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PublicSocialSearchController extends Controller
{
    public function index(Request $request, PublicSocialSearchService $search): Response
    {
        $q = trim((string) $request->query('q', ''));

        return Inertia::render('Search/Index', [
            'content' => PublicContent::common(),
            'query' => $q,
            'results' => $search->search($q),
        ]);
    }

    public function suggest(Request $request, PublicSocialSearchService $search): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));

        if (mb_strlen($q) < 2) {
            return response()->json([
                'query' => $q,
                'results' => [
                    'jobs' => [],
                    'companies' => [],
                    'profiles' => [],
                ],
            ]);
        }

        return response()->json([
            'query' => $q,
            'results' => $search->discoverySearch($q),
        ]);
    }
}
