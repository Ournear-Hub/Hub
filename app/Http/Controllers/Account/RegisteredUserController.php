<?php

namespace App\Http\Controllers\Account;

use App\Enums\RegistrationSource;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Account\RegisterRequest;
use App\Models\CandidateProfile;
use App\Models\User;
use App\Services\LegalConsentService;
use App\Services\ReferralAttributionService;
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

        return Inertia::render('Account/Register');
    }

    public function store(
        RegisterRequest $request,
        ReferralAttributionService $referrals,
        LegalConsentService $legalConsent,
    ): RedirectResponse {
        $email = strtolower($request->string('email')->toString());
        $username = User::normalizeUsername($request->string('username')->toString());

        $user = DB::transaction(function () use ($request, $referrals, $username, $email) {
            $user = User::query()->create([
                'name' => $request->string('name')->toString(),
                'username' => $username,
                'email' => $email,
                'mobile' => $request->filled('mobile') ? $request->string('mobile')->toString() : null,
                'preferred_language' => $request->filled('preferred_language')
                    ? $request->string('preferred_language')->toString()
                    : 'en',
                'password' => $request->string('password')->toString(),
                'role' => UserRole::Candidate,
                'registration_source' => RegistrationSource::Website,
            ]);

            CandidateProfile::query()->create(['user_id' => $user->id]);

            $referrals->attributeRegistration($user, $request);

            $user->forceFill([
                'profile_completion' => $user->calculateProfileCompletion(),
            ])->saveQuietly();

            return $user->fresh() ?? $user;
        });

        event(new Registered($user));

        $legalConsent->recordRegistrationConsent($user, $request);

        return redirect()
            ->route('login')
            ->with([
                'status' => 'Registration successful. Verification email sent to: '.$user->email,
                'registered_email' => $user->email,
            ]);
    }
}
