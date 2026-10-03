<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\UserPhotoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UserPhotoController extends Controller
{
    public function show(Request $request, User $user, UserPhotoService $photos): StreamedResponse
    {
        foreach (['admin', 'account'] as $guard) {
            $viewer = Auth::guard($guard)->user();
            if ($viewer instanceof User && Gate::forUser($viewer)->allows('viewPhoto', $user)) {
                return $photos->response($user);
            }
        }

        abort(403);
    }
}
