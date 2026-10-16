<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Qualification;
use App\Models\Skill;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class EmployerJobMasterDataController extends Controller
{
    public function storeQualification(Request $request): JsonResponse
    {
        abort_unless($request->user()?->canAccessHiringWorkspace() === true, 403);

        $name = Qualification::displayName(trim((string) $request->input('name', '')));
        if ($name === '') {
            throw ValidationException::withMessages(['name' => 'Enter a qualification name.']);
        }

        $qualification = Qualification::findOrCreateFromName($name);

        return response()->json([
            'id' => $qualification->id,
            'name' => $qualification->name,
        ]);
    }

    public function storeSkill(Request $request): JsonResponse
    {
        abort_unless($request->user()?->canAccessHiringWorkspace() === true, 403);

        $name = Skill::displayName(trim((string) $request->input('name', '')));
        if ($name === '') {
            throw ValidationException::withMessages(['name' => 'Enter a skill name.']);
        }

        $skill = Skill::findOrCreateFromName($name);

        return response()->json([
            'id' => $skill->id,
            'name' => $skill->name,
        ]);
    }
}
