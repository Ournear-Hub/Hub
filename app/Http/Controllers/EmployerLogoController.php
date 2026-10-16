<?php

namespace App\Http\Controllers;

use App\Models\EmployerProfile;
use App\Models\User;
use App\Services\EmployerLogoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EmployerLogoController extends Controller
{
    public function show(Request $request, EmployerProfile $employer, EmployerLogoService $logos): StreamedResponse
    {
        foreach (['admin', 'account'] as $guard) {
            $viewer = Auth::guard($guard)->user();
            if ($viewer instanceof User && Gate::forUser($viewer)->allows('viewLogo', $employer)) {
                return $logos->response($employer);
            }
        }

        if (! Auth::guard('account')->check() && ! Auth::guard('admin')->check()) {
            if ($employer->allowsPublicCompanyPage()) {
                return $logos->response($employer);
            }

            $hasPublicJob = $employer->jobs()
                ->publiclyVisible()
                ->exists();

            if ($hasPublicJob) {
                return $logos->response($employer);
            }
        }

        abort(403);
    }
}
