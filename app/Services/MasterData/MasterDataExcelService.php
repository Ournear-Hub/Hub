<?php

namespace App\Services\MasterData;

use App\Enums\MasterEntityType;
use App\Models\Industry;
use App\Models\JobCategory;
use App\Models\MasterDataImportPreview;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MasterDataExcelService
{
    private const MAX_ROWS = 2000;

    public function __construct(
        private MasterDataService $masterData,
        private MasterDataAuditService $audit,
    ) {}

    public function templateFilename(MasterEntityType $type): string
    {
        return 'master-data-'.$type->value.'-template.xlsx';
    }

    public function exportFilename(MasterEntityType $type): string
    {
        return 'master-data-'.$type->value.'-export-'.now()->format('Ymd-His').'.xlsx';
    }

    public function streamTemplate(MasterEntityType $type): StreamedResponse
    {
        $spreadsheet = $this->buildWorkbook($type, []);

        return $this->streamSpreadsheet($spreadsheet, $this->templateFilename($type));
    }

    public function streamExport(MasterEntityType $type): StreamedResponse
    {
        $rows = $this->exportRows($type);
        $spreadsheet = $this->buildWorkbook($type, $rows);

        return $this->streamSpreadsheet($spreadsheet, $this->exportFilename($type));
    }

    /**
     * @return array{token: string, summary: array<string, int>, rows: list<array<string, mixed>>}
     */
    public function preview(MasterEntityType $type, UploadedFile $file, User $actor): array
    {
        $parsed = $this->parseWorkbook($type, $file);
        $previewRows = $this->evaluateRows($type, $parsed);

        $summary = $this->summarizePreview($previewRows);

        $preview = MasterDataImportPreview::query()->create([
            'token' => Str::random(64),
            'user_id' => $actor->id,
            'entity_type' => $type->value,
            'rows' => $previewRows,
            'summary' => $summary,
            'status' => 'pending',
            'expires_at' => now()->addHours(2),
        ]);

        return [
            'token' => $preview->token,
            'summary' => $summary,
            'rows' => $previewRows,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function confirm(MasterEntityType $type, string $token, User $actor): array
    {
        return DB::transaction(function () use ($type, $token, $actor): array {
            $preview = MasterDataImportPreview::query()
                ->where('token', $token)
                ->lockForUpdate()
                ->first();

            if ($preview === null
                || $preview->user_id !== $actor->id
                || $preview->entity_type !== $type->value) {
                throw ValidationException::withMessages([
                    'token' => 'Import preview is invalid or expired.',
                ]);
            }

            if ($preview->confirmed_at !== null || $preview->status !== 'pending') {
                throw ValidationException::withMessages([
                    'token' => 'This import has already been processed.',
                ]);
            }

            if ($preview->expires_at->isPast()) {
                $preview->update(['status' => 'expired']);
                throw ValidationException::withMessages([
                    'token' => 'Import preview has expired. Upload the file again.',
                ]);
            }

            /** @var list<array<string, mixed>> $rows */
            $rows = $preview->rows ?? [];
            $outcome = [
                'created' => 0,
                'updated' => 0,
                'unchanged' => 0,
                'rejected' => 0,
                'errors' => [],
            ];

            foreach ($rows as $row) {
                $action = (string) ($row['action'] ?? 'reject');

                if ($action === 'unchanged') {
                    $outcome['unchanged']++;
                    continue;
                }

                if ($action === 'reject') {
                    $outcome['rejected']++;
                    if (! empty($row['errors'])) {
                        $outcome['errors'][] = [
                            'row' => $row['row'] ?? null,
                            'messages' => $row['errors'],
                        ];
                    }
                    continue;
                }

                try {
                    $result = $this->applyRow($type, $row, $actor);
                    $outcome[$result]++;
                } catch (\Throwable $exception) {
                    $outcome['rejected']++;
                    $outcome['errors'][] = [
                        'row' => $row['row'] ?? null,
                        'messages' => [$exception->getMessage()],
                    ];
                }
            }

            $preview->update([
                'status' => 'confirmed',
                'confirmed_at' => now(),
                'summary' => $outcome,
            ]);

            return $outcome;
        });
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function exportRows(MasterEntityType $type): array
    {
        $query = $type->modelClass()::query()->orderBy('sort_order')->orderBy('name');

        if ($type === MasterEntityType::Categories) {
            $query->with('industry:id,name');
        }

        if ($type === MasterEntityType::JobTitles) {
            $query->with('category:id,name');
        }

        return $query->limit(self::MAX_ROWS)->get()->map(function (Model $record) use ($type): array {
            $row = [
                'id' => $record->getKey(),
                'name' => $record->getAttribute('name'),
            ];

            if ($type->hasCode()) {
                $row['code'] = $record->getAttribute('code');
            }

            if ($type->hasDescription()) {
                $row['description'] = $record->getAttribute('description');
            }

            if ($type === MasterEntityType::Categories) {
                $row['industry_name'] = $record->industry?->name;
            }

            if ($type === MasterEntityType::JobTitles) {
                $row['job_category_name'] = $record->category?->name;
            }

            $row['sort_order'] = (int) $record->getAttribute('sort_order');
            $row['is_active'] = (bool) $record->getAttribute('is_active') ? 'yes' : 'no';

            return $row;
        })->all();
    }

    /**
     * @param  list<array<string, mixed>>  $dataRows
     */
    private function buildWorkbook(MasterEntityType $type, array $dataRows): Spreadsheet
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $headers = MasterDataExcelSchema::headers($type);

        $sheet->fromArray([$headers], null, 'A1');

        $instructionRow = implode(' ', MasterDataExcelSchema::instructions($type));
        $sheet->fromArray([[$instructionRow]], null, 'A2');

        $rowNumber = 3;
        foreach ($dataRows as $data) {
            $line = [];
            foreach ($headers as $header) {
                $line[] = $data[$header] ?? '';
            }
            $sheet->fromArray([$line], null, 'A'.$rowNumber);
            $rowNumber++;
        }

        return $spreadsheet;
    }

    private function streamSpreadsheet(Spreadsheet $spreadsheet, string $filename): StreamedResponse
    {
        return response()->streamDownload(function () use ($spreadsheet): void {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function parseWorkbook(MasterEntityType $type, UploadedFile $file): array
    {
        $extension = strtolower($file->getClientOriginalExtension());
        if (! in_array($extension, ['xlsx', 'xls', 'csv'], true)) {
            throw ValidationException::withMessages([
                'file' => 'Upload an Excel workbook (.xlsx, .xls) or CSV file.',
            ]);
        }

        $spreadsheet = IOFactory::load($file->getRealPath());
        $rows = $spreadsheet->getActiveSheet()->toArray(null, true, true, false);

        if ($rows === []) {
            throw ValidationException::withMessages(['file' => 'The workbook is empty.']);
        }

        $headerRow = array_map(fn ($value) => $this->normalizeHeader((string) $value), $rows[0]);
        $expected = MasterDataExcelSchema::headers($type);

        foreach ($expected as $header) {
            if (! in_array($header, $headerRow, true)) {
                throw ValidationException::withMessages([
                    'file' => 'Missing required column: '.$header,
                ]);
            }
        }

        $parsed = [];
        for ($index = 1; $index < count($rows); $index++) {
            $line = $rows[$index];
            if ($this->rowIsEmpty($line)) {
                continue;
            }

            $assoc = [];
            foreach ($headerRow as $colIndex => $header) {
                if ($header === '') {
                    continue;
                }
                $assoc[$header] = trim((string) ($line[$colIndex] ?? ''));
            }

            if ($this->isInstructionRow($assoc)) {
                continue;
            }

            $parsed[] = $assoc;

            if (count($parsed) > self::MAX_ROWS) {
                throw ValidationException::withMessages([
                    'file' => 'Workbooks are limited to '.self::MAX_ROWS.' data rows.',
                ]);
            }
        }

        return $parsed;
    }

    /**
     * @param  list<array<string, mixed>>  $parsed
     * @return list<array<string, mixed>>
     */
    private function evaluateRows(MasterEntityType $type, array $parsed): array
    {
        $preview = [];
        $seenNames = [];

        foreach ($parsed as $offset => $data) {
            $rowNumber = $offset + 2;
            $errors = [];
            $action = 'apply';

            $nameKey = mb_strtolower(trim((string) ($data['name'] ?? '')));
            if ($nameKey !== '') {
                $scope = $type === MasterEntityType::Categories
                    ? ($data['industry_name'] ?? '')
                    : ($type === MasterEntityType::JobTitles ? ($data['job_category_name'] ?? '') : '');
                $dedupeKey = $nameKey.'|'.$scope;
                if (isset($seenNames[$dedupeKey])) {
                    $errors[] = 'Duplicate name in file.';
                }
                $seenNames[$dedupeKey] = true;
            }

            $record = $this->resolveExisting($type, $data);
            $id = trim((string) ($data['id'] ?? ''));

            if ($id !== '' && $record === null) {
                $errors[] = 'Unknown id '.$id.'.';
            }

            if ($record === null && ! $type->allowsCreate()) {
                $errors[] = 'New rows are not allowed for this master-data type.';
            }

            if (trim((string) ($data['name'] ?? '')) === '' && $record === null) {
                $errors[] = 'Name is required for new rows.';
            }

            $payload = $this->buildPayload($type, $data, $record, $errors);

            if ($errors !== []) {
                $action = 'reject';
            } elseif ($record === null) {
                $action = 'apply';
            } elseif ($this->payloadMatches($type, $record, $payload) === true) {
                $action = 'unchanged';
            }

            $preview[] = [
                'row' => $rowNumber,
                'action' => $action === 'unchanged' ? 'unchanged' : ($action === 'reject' ? 'reject' : 'apply'),
                'intent' => $record === null ? 'create' : 'update',
                'errors' => $errors,
                'payload' => $payload,
                'existing_id' => $record?->getKey(),
            ];
        }

        return $preview;
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return array<string, int>
     */
    private function summarizePreview(array $rows): array
    {
        $summary = ['create' => 0, 'update' => 0, 'unchanged' => 0, 'reject' => 0];

        foreach ($rows as $row) {
            if (($row['action'] ?? '') === 'reject') {
                $summary['reject']++;
                continue;
            }
            if (($row['action'] ?? '') === 'unchanged') {
                $summary['unchanged']++;
                continue;
            }
            if (($row['intent'] ?? '') === 'create') {
                $summary['create']++;
            } else {
                $summary['update']++;
            }
        }

        return $summary;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function resolveExisting(MasterEntityType $type, array $data): ?Model
    {
        $id = trim((string) ($data['id'] ?? ''));
        if ($id !== '') {
            return $type->modelClass()::query()->find($id);
        }

        $name = trim((string) ($data['name'] ?? ''));
        if ($name === '') {
            return null;
        }

        $query = $type->modelClass()::query()->whereRaw('LOWER(name) = ?', [mb_strtolower($name)]);

        if ($type === MasterEntityType::Categories) {
            $industry = Industry::findByName((string) ($data['industry_name'] ?? ''));
            if ($industry === null) {
                return null;
            }
            $query->where('industry_id', $industry->id);
        }

        if ($type === MasterEntityType::JobTitles) {
            $category = JobCategory::query()
                ->whereRaw('LOWER(name) = ?', [mb_strtolower((string) ($data['job_category_name'] ?? ''))])
                ->first();
            if ($category === null) {
                return null;
            }
            $query->where('job_category_id', $category->id);
        }

        return $query->first();
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  list<string>  $errors
     * @return array<string, mixed>
     */
    private function buildPayload(MasterEntityType $type, array $data, ?Model $record, array &$errors): array
    {
        $payload = [
            'name' => trim((string) ($data['name'] ?? ($record?->getAttribute('name') ?? ''))),
            'sort_order' => $this->parseInt($data['sort_order'] ?? $record?->getAttribute('sort_order') ?? 0, 'sort_order', $errors),
            'is_active' => $this->parseBool($data['is_active'] ?? ($record?->getAttribute('is_active') ? 'yes' : 'no'), $errors),
        ];

        if ($type->hasDescription()) {
            $payload['description'] = trim((string) ($data['description'] ?? $record?->getAttribute('description') ?? ''));
        }

        if ($type === MasterEntityType::Categories) {
            $industry = Industry::findByName((string) ($data['industry_name'] ?? ''));
            if ($industry === null || ! $industry->is_active) {
                $errors[] = 'industry_name must match an active industry.';
            } else {
                $payload['industry_id'] = $industry->id;
            }
        }

        if ($type === MasterEntityType::JobTitles) {
            $category = JobCategory::query()
                ->with('industry')
                ->whereRaw('LOWER(name) = ?', [mb_strtolower((string) ($data['job_category_name'] ?? ''))])
                ->first();
            if ($category === null || ! $category->isSelectable()) {
                $errors[] = 'job_category_name must match an active category.';
            } else {
                $payload['job_category_id'] = $category->id;
            }
        }

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function payloadMatches(MasterEntityType $type, Model $record, array $payload): bool
    {
        foreach (['name', 'sort_order', 'description'] as $field) {
            if (! array_key_exists($field, $payload)) {
                continue;
            }
            if ((string) $record->getAttribute($field) !== (string) $payload[$field]) {
                return false;
            }
        }

        if ((bool) $record->getAttribute('is_active') !== (bool) $payload['is_active']) {
            return false;
        }

        if ($type === MasterEntityType::Categories
            && (int) $record->getAttribute('industry_id') !== (int) ($payload['industry_id'] ?? 0)) {
            return false;
        }

        if ($type === MasterEntityType::JobTitles
            && (int) $record->getAttribute('job_category_id') !== (int) ($payload['job_category_id'] ?? 0)) {
            return false;
        }

        return true;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function applyRow(MasterEntityType $type, array $row, User $actor): string
    {
        /** @var array<string, mixed> $payload */
        $payload = $row['payload'] ?? [];
        $existingId = $row['existing_id'] ?? null;

        if ($existingId) {
            $record = $this->masterData->resolve($type, (int) $existingId);
            $this->masterData->update($type, $record, $payload, $actor);
            $active = (bool) ($payload['is_active'] ?? true);
            if ((bool) $record->fresh()->is_active !== $active) {
                $this->masterData->toggleStatus($type, $record->fresh(), $active, $actor);
            }

            return 'updated';
        }

        $this->masterData->create($type, $payload, $actor);

        return 'created';
    }

    private function normalizeHeader(string $value): string
    {
        return strtolower(trim(str_replace(' ', '_', $value)));
    }

    /**
     * @param  array<int, mixed>  $line
     */
    private function rowIsEmpty(array $line): bool
    {
        foreach ($line as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array<string, mixed>  $assoc
     */
    private function isInstructionRow(array $assoc): bool
    {
        $joined = strtolower(implode(' ', array_values($assoc)));

        return str_contains($joined, 'leave id empty') || str_contains($joined, 'is_active');
    }

    /**
     * @param  list<string>  $errors
     */
    private function parseBool(mixed $value, array &$errors): bool
    {
        $normalized = strtolower(trim((string) $value));
        if (in_array($normalized, ['1', 'yes', 'true', 'y'], true)) {
            return true;
        }
        if (in_array($normalized, ['0', 'no', 'false', 'n'], true)) {
            return false;
        }
        $errors[] = 'is_active must be yes/no.';

        return true;
    }

    /**
     * @param  list<string>  $errors
     */
    private function parseInt(mixed $value, string $field, array &$errors): int
    {
        if ($value === '' || $value === null) {
            return 0;
        }
        if (! is_numeric($value)) {
            $errors[] = $field.' must be numeric.';

            return 0;
        }

        return (int) $value;
    }
}
