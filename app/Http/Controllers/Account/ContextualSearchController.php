<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Services\AccountContextualSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContextualSearchController extends Controller
{
    public function __invoke(Request $request, AccountContextualSearchService $search): JsonResponse
    {
        $user = $request->user();
        abort_unless($user !== null, 403);

        $validated = $request->validate([
            'q' => ['required', 'string', 'min:2', 'max:120'],
            'scope' => ['nullable', 'string', 'in:auto,candidate,employer'],
        ]);

        return response()->json(
            $search->forUser($user, $validated['q'], $validated['scope'] ?? 'auto'),
        );
    }
}
