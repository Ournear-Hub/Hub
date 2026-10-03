<?php

namespace App\Http\Controllers\Account;

use App\Enums\UserActivityType;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\UserActivityService;
use App\Support\IntendedUrl;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EmailVerificationController extends Controller
{
    public function send(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User && $user->isAccountMember(), 403);
        $this->authorize('update', $user);

        if ($user->hasVerifiedEmail()) {
            return back()->with('status', 'Your email is already verified.');
        }

        $user->sendEmailVerificationNotification();

        return back()->with('status', 'A verification link has been sent to your email address.');
    }

    public function verify(EmailVerificationRequest $request, UserActivityService $activities): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        $alreadyVerified = $user->hasVerifiedEmail();

        if (! $alreadyVerified) {
            $request->fulfill();
            $activities->record(
                $user,
                UserActivityType::EmailVerified,
                'Email address was verified.',
                $user,
            );
        }

        $home = $user->isAdmin()
            ? route('admin.dashboard')
            : route('account.settings.security');
        $destination = IntendedUrl::pullAllowed(
            $request,
            $user->isAdmin() ? ['/admin'] : ['/account', '/jobs'],
            $home,
        );

        if ($alreadyVerified) {
            return redirect()->to($destination)->with('status', 'Your email is already verified.');
        }

        return redirect()->to($destination)->with('success', 'Your email has been verified.');
    }
}
