<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MobileEmailVerificationController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $this->authorize('update', $user);

        return response()->json([
            'data' => [
                'email' => $user->email,
                'email_verified' => $user->hasVerifiedEmail(),
                'verified_at' => $user->email_verified_at?->toIso8601String(),
            ],
        ]);
    }

    public function resend(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = User::query()->findOrFail($request->user()->getKey());
        $this->authorize('update', $user);

        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Your email is already verified.',
                'data' => [
                    'email_verified' => true,
                ],
            ]);
        }

        $user->sendEmailVerificationNotification();

        return response()->json([
            'message' => 'A verification link has been sent to your email address.',
            'data' => [
                'email_verified' => false,
            ],
        ]);
    }
}
