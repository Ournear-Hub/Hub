<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Services\EmployerStaffingAccessService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CompanyStaffingAccessController extends Controller
{
    public function requestAccess(Request $request, EmployerStaffingAccessService $staffingAccess): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user !== null, 403);
        $profile = $user->employerProfile;
        abort_unless($profile !== null, 403);

        $staffingAccess->requestAccess($user, $profile);

        return back()->with('success', 'Staffing access request submitted for admin review.');
    }
}
