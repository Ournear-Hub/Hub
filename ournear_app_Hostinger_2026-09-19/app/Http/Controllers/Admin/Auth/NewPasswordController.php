<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ResetPasswordRequest;
use App\Models\User;
use App\Services\LoginLogService;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class NewPasswordController extends Controller
{
    public function create(Request $request): Response
    {
        return Inertia::render('Admin/Auth/ResetPassword', [
            'email' => $request->string('email')->toString(),
            'token' => $request->route('token'),
        ]);
    }

    public function store(ResetPasswordRequest $request, LoginLogService $logs): RedirectResponse
    {
        $account = User::query()->where('email', $request->string('email')->toString())->first();

        if (! $account?->isAdmin()) {
            return back()->withErrors(['email' => __('passwords.token')]);
        }

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user) use ($request, $logs) {
                $user->forceFill([
                    'password' => Hash::make($request->string('password')->toString()),
                    'remember_token' => Str::random(40),
                ])->save();

                event(new PasswordReset($user));
                if ($user instanceof User) {
                    $logs->passwordResetSuccess($request, $user);
                }
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('admin.login')->with('status', 'Your password has been reset.');
        }

        return back()->withErrors(['email' => __($status)]);
    }
}
