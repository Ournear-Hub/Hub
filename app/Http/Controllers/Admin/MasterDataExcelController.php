<?php

namespace App\Http\Controllers\Admin;

use App\Enums\MasterEntityType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ConfirmMasterDataImportRequest;
use App\Http\Requests\Admin\PreviewMasterDataImportRequest;
use App\Services\MasterData\MasterDataExcelService;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MasterDataExcelController extends Controller
{
    public function template(string $type, MasterDataExcelService $excel): StreamedResponse
    {
        $entity = $this->resolveType($type);
        $this->authorize('viewAny', $entity->modelClass());

        return $excel->streamTemplate($entity);
    }

    public function export(string $type, MasterDataExcelService $excel): StreamedResponse
    {
        $entity = $this->resolveType($type);
        $this->authorize('viewAny', $entity->modelClass());

        return $excel->streamExport($entity);
    }

    public function preview(PreviewMasterDataImportRequest $request, MasterDataExcelService $excel): RedirectResponse
    {
        $type = $request->entityType();
        abort_if($type === null, 404);

        $result = $excel->preview($type, $request->file('file'), $request->user());

        return back()->with('masterDataImportPreview', $result);
    }

    public function confirm(ConfirmMasterDataImportRequest $request, MasterDataExcelService $excel): RedirectResponse
    {
        $type = $request->entityType();
        abort_if($type === null, 404);

        $outcome = $excel->confirm($type, $request->string('token')->toString(), $request->user());

        return back()->with('success', sprintf(
            'Import complete: %d created, %d updated, %d unchanged, %d rejected.',
            $outcome['created'],
            $outcome['updated'],
            $outcome['unchanged'],
            $outcome['rejected'],
        ))->with('masterDataImportOutcome', $outcome);
    }

    private function resolveType(string $type): MasterEntityType
    {
        $entity = MasterEntityType::tryFrom($type);
        abort_if($entity === null, 404);

        return $entity;
    }
}
