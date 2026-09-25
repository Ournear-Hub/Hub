<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\UserPhotoService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UserPhotoController extends Controller
{
    public function show(Request $request, User $user, UserPhotoService $photos): StreamedResponse
    {
        $this->authorize('viewPhoto', $user);

        return $photos->response($user);
    }
}
