<?php

namespace App\Http\Controllers\Account;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Account\RegisterRequest;
use App\Models\CandidateProfile;
use App\Models\EmployerProfile;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Account/Register');
    }

    public function store(RegisterRequest $request): RedirectResponse
    {
        $role = UserRole::from($request->string('role')->toString());

        $user = DB::transaction(function () use ($request, $role) {
            $user = User::query()->create([
                'name' => $request->string('name')->toString(),
                'email' => strtolower($request->string('email')->toString()),
                'password' => $request->string('password')->toString(),
                'role' => $role,
            ]);

            if ($role === UserRole::Candidate) {
                CandidateProfile::query()->create(['user_id' => $user->id]);
            } else {
                EmployerProfile::query()->create(['user_id' => $user->id]);
            }

            return $user;
        });

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('account.home');
    }
}
