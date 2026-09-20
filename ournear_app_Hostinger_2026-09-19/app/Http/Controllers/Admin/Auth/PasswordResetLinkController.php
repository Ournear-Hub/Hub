<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ForgotPasswordRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Password;
use Inertia\Inertia;
use Inertia\Response;

class PasswordResetLinkController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Admin/Auth/ForgotPassword', [
            'status' => session('status'),
        ]);
    }

    public function store(ForgotPasswordRequest $request): RedirectResponse
    {
        $email = strtolower($request->string('email')->toString());
        $user = User::query()->whereRaw('LOWER(email) = ?', [$email])->first();

        if ($user?->isAdmin()) {
            Password::sendResetLink($request->only('email'));
        }

        return back()->with('status', 'A password reset link has been sent if that account exists.');
    }
}
