<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UsernameAvailabilityController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $username = User::normalizeUsername($request->query('username'));

        if ($username === '' || ! User::usernameIsValid($username)) {
            return response()->json(['available' => false], 422);
        }

        $available = ! User::query()->where('username', $username)->exists();

        return response()->json(['available' => $available]);
    }
}
