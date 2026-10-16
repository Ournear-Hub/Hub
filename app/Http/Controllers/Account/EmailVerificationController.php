<?php

namespace App\Http\Controllers\Account;

use App\Enums\UserActivityType;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\EmployerPostingEligibilityService;
use App\Services\UserActivityService;
use App\Support\IntendedUrl;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EmailVerificationController extends Controller
{
    public function send(Request $request): RedirectResponse
    {
        $sessionUser = $request->user();
        abort_unless($sessionUser instanceof User && $sessionUser->isAccountMember(), 403);
        $user = User::query()->findOrFail($sessionUser->getKey());
        $this->authorize('update', $user);

        if ($user->hasVerifiedEmail()) {
            return back()->with('status', 'Your email is already verified.');
        }

        $user->sendEmailVerificationNotification();

        return back()->with('status', 'A verification link has been sent to your email address.');
    }

    public function verify(Request $request, int $id, string $hash, UserActivityService $activities): RedirectResponse
    {
        $user = User::query()
            ->whereKey($id)
            ->whereIn('role', [\App\Enums\UserRole::Candidate, \App\Enums\UserRole::Employer])
            ->first();

        if (! $user instanceof User) {
            return redirect()
                ->route('login')
                ->withErrors(['email' => 'This verification link is invalid or has expired.']);
        }

        if (! hash_equals(sha1($user->getEmailForVerification()), (string) $hash)) {
            return redirect()
                ->route('login')
                ->withErrors(['email' => 'This verification link is invalid or has expired.']);
        }

        $sessionUser = Auth::guard('account')->user();
        if ($sessionUser instanceof User && $sessionUser->getKey() !== $user->getKey()) {
            abort(403);
        }

        if (! $sessionUser instanceof User) {
            Auth::guard('account')->login($user);
            $request->session()->regenerate();
        }

        $alreadyVerified = $user->hasVerifiedEmail();

        if (! $alreadyVerified) {
            $user->markEmailAsVerified();
            event(new Verified($user));
            $activities->record(
                $user,
                UserActivityType::EmailVerified,
                'Email address was verified.',
                $user,
            );

            app(EmployerPostingEligibilityService::class)->syncPublishingBenefits($user->fresh());
            $request->session()->forget('prompt_email_verification');
        }

        $destination = IntendedUrl::pullAllowed(
            $request,
            $user->isAdmin() ? ['/admin'] : ['/account', '/jobs'],
            route('account.settings.verification'),
        );

        if ($alreadyVerified) {
            return redirect()
                ->to($destination)
                ->with('status', 'Your email is already verified.');
        }

        return redirect()
            ->to($destination)
            ->with('success', 'Your email has been verified successfully.');
    }

    public function dismissReminder(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User && $user->isAccountMember(), 403);

        $request->session()->forget('prompt_email_verification');

        return back();
    }
}
