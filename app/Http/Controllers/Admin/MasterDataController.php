<?php

namespace App\Http\Controllers\Admin;

use App\Enums\MasterEntityType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreMasterRecordRequest;
use App\Http\Requests\Admin\ToggleMasterRecordStatusRequest;
use App\Http\Requests\Admin\UpdateMasterRecordRequest;
use App\Services\MasterData\MasterDataService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MasterDataController extends Controller
{
    public function index(Request $request, MasterDataService $masterData): Response
    {
        $type = MasterEntityType::tryFrom((string) $request->string('tab')->toString())
            ?? MasterEntityType::Industries;

        $this->authorize('viewAny', $type->modelClass());

        return Inertia::render('Admin/MasterData/Index', $masterData->workspace($request));
    }

    public function store(StoreMasterRecordRequest $request, MasterDataService $masterData): RedirectResponse
    {
        $type = $request->entityType();
        abort_if($type === null, 404);

        $masterData->create($type, $request->validated(), $request->user());

        return back()->with('success', $type->singular().' created.');
    }

    public function update(UpdateMasterRecordRequest $request, MasterDataService $masterData): RedirectResponse
    {
        $type = $request->entityType();
        abort_if($type === null, 404);

        $masterData->update($type, $request->record(), $request->validated(), $request->user());

        return back()->with('success', $type->singular().' updated.');
    }

    public function toggleStatus(ToggleMasterRecordStatusRequest $request, MasterDataService $masterData): RedirectResponse
    {
        $type = $request->entityType();
        abort_if($type === null, 404);

        $isActive = $request->boolean('is_active');
        $masterData->toggleStatus($type, $request->record(), $isActive, $request->user());

        return back()->with('success', $isActive
            ? $type->singular().' activated.'
            : $type->singular().' deactivated.');
    }
}
