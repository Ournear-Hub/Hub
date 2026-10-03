<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Http\Requests\Account\ForgotPasswordRequest;
use App\Models\User;
use App\Services\LoginLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Password;
use Inertia\Inertia;
use Inertia\Response;

class PasswordResetLinkController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Account/ForgotPassword', [
            'status' => session('status'),
        ]);
    }

    public function store(ForgotPasswordRequest $request, LoginLogService $logs): RedirectResponse
    {
        $email = strtolower($request->string('email')->toString());
        $user = User::query()->whereRaw('LOWER(email) = ?', [$email])->first();

        if ($user?->isAccountMember()) {
            Password::sendResetLink($request->only('email'));
            $logs->passwordResetRequest($request, $user, $email);
        } else {
            $logs->passwordResetRequest($request, null, $email);
        }

        return back()->with('status', 'A password reset link has been sent if that account exists.');
    }
}
