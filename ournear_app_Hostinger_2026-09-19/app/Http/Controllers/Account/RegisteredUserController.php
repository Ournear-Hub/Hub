<?php

namespace App\Http\Controllers\Account;

use App\Enums\PreferredLanguage;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Account\RegisterRequest;
use App\Models\CandidateProfile;
use App\Models\EmployerProfile;
use App\Models\User;
use App\Support\IntendedUrl;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    public function create(Request $request): Response|RedirectResponse
    {
        $user = Auth::guard('account')->user();
        if ($user instanceof User) {
            return redirect()->route('account.home');
        }

        IntendedUrl::remember($request);

        return Inertia::render('Account/Register', [
            'languages' => collect(PreferredLanguage::cases())
                ->map(fn (PreferredLanguage $language) => [
                    'value' => $language->value,
                    'label' => $language->label(),
                ])
                ->values()
                ->all(),
        ]);
    }

    public function store(RegisterRequest $request): RedirectResponse
    {
        $role = UserRole::from($request->string('role')->toString());

        $user = DB::transaction(function () use ($request, $role) {
            $user = User::query()->create([
                'name' => $request->string('name')->toString(),
                'username' => User::normalizeUsername($request->string('username')->toString()),
                'email' => strtolower($request->string('email')->toString()),
                'mobile' => $request->filled('mobile') ? $request->string('mobile')->toString() : null,
                'preferred_language' => $request->filled('preferred_language')
                    ? $request->string('preferred_language')->toString()
                    : 'en',
                'password' => $request->string('password')->toString(),
                'role' => $role,
            ]);

            if ($role === UserRole::Candidate) {
                CandidateProfile::query()->create(['user_id' => $user->id]);
            } else {
                EmployerProfile::query()->create(['user_id' => $user->id]);
            }

            $user->forceFill([
                'profile_completion' => $user->calculateProfileCompletion(),
            ])->saveQuietly();

            return $user;
        });

        event(new Registered($user));

        Auth::guard('account')->login($user);
        $request->session()->regenerate();

        return redirect()->to(IntendedUrl::pullAllowed(
            $request,
            ['/account', '/jobs'],
            route('account.home'),
        ))->with([
            'status' => 'Verification email sent to: '.$user->email,
            'registered_email' => $user->email,
        ]);
    }
}
