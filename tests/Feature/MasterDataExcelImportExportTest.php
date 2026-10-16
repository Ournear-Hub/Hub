<?php

namespace Tests\Feature;

use App\Enums\MasterEntityType;
use App\Models\ApplicationStatusOption;
use App\Models\Industry;
use App\Models\Job;
use App\Models\JobCategory;
use App\Models\JobStatusOption;
use App\Models\User;
use App\Services\MasterData\MasterDataBootstrapper;
use App\Services\MasterData\MasterDataExcelSchema;
use App\Services\MasterData\MasterDataExcelService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\Support\MasterDataImportWorkbookFactory;
use Tests\TestCase;

class MasterDataExcelImportExportTest extends TestCase
{
    public function test_guest_cannot_access_master_data_excel_routes(): void
    {
        $this->get(route('admin.master-data.excel.export', ['type' => 'industries']))
            ->assertRedirect(route('admin.login'));
    }

    public function test_admin_can_download_template_and_export(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->get(route('admin.master-data.excel.template', ['type' => 'industries']))
            ->assertOk()
            ->assertHeader('content-disposition');

        $this->actingAs($admin)
            ->get(route('admin.master-data.excel.export', ['type' => 'industries']))
            ->assertOk()
            ->assertHeader('content-disposition');
    }

    public function test_exported_workbook_headers_match_schema(): void
    {
        $admin = $this->makeAdmin();
        $this->seedIndustry($admin, 'Export Header Industry');

        $response = $this->actingAs($admin)
            ->get(route('admin.master-data.excel.export', ['type' => 'industries']));

        $response->assertOk();
        $content = $response->streamedContent();
        $this->assertNotEmpty($content);

        $path = Storage::disk('local')->path('tests/read-export-'.uniqid('', true).'.xlsx');
        file_put_contents($path, $content);

        $sheet = IOFactory::load($path)->getActiveSheet()->toArray(null, true, true, false);
        $headers = array_map(fn ($value) => strtolower(trim(str_replace(' ', '_', (string) $value))), $sheet[0]);

        foreach (MasterDataExcelSchema::headers(MasterEntityType::Industries) as $expected) {
            $this->assertContains($expected, $headers, "Missing header {$expected}");
        }
    }

    public function test_export_with_empty_dataset_is_valid_workbook(): void
    {
        $admin = $this->makeAdmin();

        $response = $this->actingAs($admin)
            ->get(route('admin.master-data.excel.template', ['type' => 'locations']));

        $content = $response->streamedContent();
        $path = Storage::disk('local')->path('tests/empty-template-'.uniqid('', true).'.xlsx');
        file_put_contents($path, $content);

        $rows = IOFactory::load($path)->getActiveSheet()->toArray(null, true, true, false);
        $this->assertNotEmpty($rows[0]);
    }

    public function test_candidate_and_employer_cannot_access_master_data_excel_routes(): void
    {
        $this->actingAs($this->makeCandidate())
            ->get(route('admin.master-data.excel.export', ['type' => 'industries']))
            ->assertForbidden();

        $this->actingAs($this->makeEmployer())
            ->get(route('admin.master-data.excel.export', ['type' => 'industries']))
            ->assertForbidden();
    }

    public function test_preview_does_not_mutate_database_and_confirm_creates_row(): void
    {
        $admin = $this->makeAdmin();
        $before = Industry::query()->count();

        $file = $this->makeWorkbook(MasterEntityType::Industries, [[
            'id' => '',
            'name' => 'Excel Import Industry QA',
            'description' => 'From workbook',
            'sort_order' => '15',
            'is_active' => 'yes',
        ]]);

        $this->actingAs($admin)
            ->post(route('admin.master-data.excel.preview', ['type' => 'industries']), [
                'file' => $file,
            ])
            ->assertRedirect()
            ->assertSessionHas('masterDataImportPreview');

        $this->assertSame($before, Industry::query()->count());

        $preview = session('masterDataImportPreview');
        $this->assertIsArray($preview);
        $this->assertNotEmpty($preview['token']);
        $this->assertSame(1, $preview['summary']['create'] ?? 0);

        $this->actingAs($admin)
            ->post(route('admin.master-data.excel.confirm', ['type' => 'industries']), [
                'token' => $preview['token'],
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame($before + 1, Industry::query()->count());
        $this->assertNotNull(Industry::findByName('Excel Import Industry QA'));
    }

    public function test_import_resolves_category_foreign_key_by_industry_name(): void
    {
        $admin = $this->makeAdmin();
        $industry = $this->seedIndustry($admin, 'Excel FK Industry');

        $file = $this->makeWorkbook(MasterEntityType::Categories, [[
            'id' => '',
            'name' => 'Excel FK Category',
            'description' => '',
            'industry_name' => $industry->name,
            'sort_order' => '5',
            'is_active' => 'yes',
        ]]);

        $preview = app(MasterDataExcelService::class)->preview(MasterEntityType::Categories, $file, $admin);
        $this->assertSame(0, $preview['summary']['reject']);

        app(MasterDataExcelService::class)->confirm(MasterEntityType::Categories, $preview['token'], $admin);

        $category = JobCategory::query()->where('name', 'Excel FK Category')->first();
        $this->assertNotNull($category);
        $this->assertSame($industry->id, $category->industry_id);
    }

    public function test_existing_record_is_matched_by_id_for_update(): void
    {
        $admin = $this->makeAdmin();
        $industry = $this->seedIndustry($admin, 'Excel Match By Id');

        $file = $this->makeWorkbook(MasterEntityType::Industries, [[
            'id' => (string) $industry->id,
            'name' => 'Excel Match By Id Updated',
            'description' => 'Updated via id',
            'sort_order' => '2',
            'is_active' => 'yes',
        ]]);

        $preview = app(MasterDataExcelService::class)->preview(MasterEntityType::Industries, $file, $admin);
        $this->assertSame(1, $preview['summary']['update']);

        app(MasterDataExcelService::class)->confirm(MasterEntityType::Industries, $preview['token'], $admin);

        $this->assertSame(1, Industry::query()->where('name', 'Excel Match By Id Updated')->count());
        $this->assertNull(Industry::findByName('Excel Match By Id'));
    }

    public function test_reimporting_the_same_workbook_does_not_create_duplicates(): void
    {
        $admin = $this->makeAdmin();
        $file = $this->makeWorkbook(MasterEntityType::Industries, [[
            'id' => '',
            'name' => 'Excel Idempotent Industry',
            'description' => '',
            'sort_order' => '1',
            'is_active' => 'yes',
        ]]);

        $service = app(MasterDataExcelService::class);
        $first = $service->preview(MasterEntityType::Industries, $file, $admin);
        $service->confirm(MasterEntityType::Industries, $first['token'], $admin);

        $secondFile = $this->makeWorkbook(MasterEntityType::Industries, [[
            'id' => '',
            'name' => 'Excel Idempotent Industry',
            'description' => '',
            'sort_order' => '1',
            'is_active' => 'yes',
        ]]);

        $second = $service->preview(MasterEntityType::Industries, $secondFile, $admin);
        $this->assertSame(1, $second['summary']['unchanged']);
        $this->assertSame(0, $second['summary']['create']);

        $outcome = $service->confirm(MasterEntityType::Industries, $second['token'], $admin);
        $this->assertSame(0, $outcome['created']);
        $this->assertSame(1, Industry::query()->where('name', 'Excel Idempotent Industry')->count());
    }

    public function test_invalid_file_type_is_rejected(): void
    {
        $admin = $this->makeAdmin();
        $file = UploadedFile::fake()->create('notes.txt', 10, 'text/plain');

        $this->actingAs($admin)
            ->post(route('admin.master-data.excel.preview', ['type' => 'industries']), [
                'file' => $file,
            ])
            ->assertSessionHasErrors('file');
    }

    public function test_missing_header_rejects_upload(): void
    {
        $admin = $this->makeAdmin();
        $spreadsheet = new Spreadsheet;
        $spreadsheet->getActiveSheet()->fromArray([['id', 'description']], null, 'A1');
        $path = Storage::disk('local')->path('tests/bad-master-import.xlsx');
        @mkdir(dirname($path), 0777, true);
        (new Xlsx($spreadsheet))->save($path);

        $file = new UploadedFile($path, 'bad-master-import.xlsx', null, null, true);

        $this->actingAs($admin)
            ->post(route('admin.master-data.excel.preview', ['type' => 'industries']), [
                'file' => $file,
            ])
            ->assertSessionHasErrors('file');
    }

    public function test_invalid_foreign_key_and_row_values_are_rejected_in_preview(): void
    {
        $admin = $this->makeAdmin();
        $file = $this->makeWorkbook(MasterEntityType::Categories, [[
            'id' => '',
            'name' => 'Bad FK Category',
            'description' => '',
            'industry_name' => 'Nonexistent Industry Name',
            'sort_order' => 'not-a-number',
            'is_active' => 'maybe',
        ]]);

        $preview = app(MasterDataExcelService::class)->preview(MasterEntityType::Categories, $file, $admin);
        $this->assertSame(1, $preview['summary']['reject']);
        $this->assertNotEmpty($preview['rows'][0]['errors']);
        $this->assertNull(JobCategory::query()->where('name', 'Bad FK Category')->first());
    }

    public function test_duplicate_rows_in_workbook_are_rejected(): void
    {
        $admin = $this->makeAdmin();
        $row = [
            'id' => '',
            'name' => 'Duplicate In File',
            'description' => '',
            'sort_order' => '1',
            'is_active' => 'yes',
        ];

        $file = $this->makeWorkbook(MasterEntityType::Industries, [$row, $row]);
        $preview = app(MasterDataExcelService::class)->preview(MasterEntityType::Industries, $file, $admin);

        $this->assertSame(1, $preview['summary']['reject']);
        $this->assertStringContainsString('Duplicate name in file', implode(' ', $preview['rows'][1]['errors']));
    }

    public function test_update_only_master_data_type_rejects_new_rows_without_id(): void
    {
        $admin = $this->makeAdmin();
        $file = $this->makeWorkbook(MasterEntityType::JobStatuses, [[
            'id' => '',
            'code' => 'NEW',
            'name' => 'Should Not Create',
            'sort_order' => '1',
            'is_active' => 'yes',
        ]]);

        $preview = app(MasterDataExcelService::class)->preview(MasterEntityType::JobStatuses, $file, $admin);
        $this->assertSame(1, $preview['summary']['reject']);
        $this->assertNull(JobStatusOption::query()->where('code', 'NEW')->first());
    }

    public function test_confirm_applies_valid_rows_and_rejects_invalid_rows_without_partial_mismatch_summary(): void
    {
        $admin = $this->makeAdmin();
        $before = Industry::query()->count();

        $file = $this->makeWorkbook(MasterEntityType::Industries, [
            [
                'id' => '',
                'name' => 'Excel Partial Good Row',
                'description' => '',
                'sort_order' => '1',
                'is_active' => 'yes',
            ],
            [
                'id' => '',
                'name' => '',
                'description' => '',
                'sort_order' => '1',
                'is_active' => 'yes',
            ],
        ]);

        $preview = app(MasterDataExcelService::class)->preview(MasterEntityType::Industries, $file, $admin);
        $this->assertSame(1, $preview['summary']['create']);
        $this->assertSame(1, $preview['summary']['reject']);

        $outcome = app(MasterDataExcelService::class)->confirm(MasterEntityType::Industries, $preview['token'], $admin);
        $this->assertSame(1, $outcome['created']);
        $this->assertSame(1, $outcome['rejected']);
        $this->assertSame($before + 1, Industry::query()->count());
    }

    public function test_duplicate_confirm_is_rejected(): void
    {
        $admin = $this->makeAdmin();
        $service = app(MasterDataExcelService::class);
        $file = $this->makeWorkbook(MasterEntityType::Industries, [[
            'id' => '',
            'name' => 'Excel Duplicate Guard',
            'description' => '',
            'sort_order' => '1',
            'is_active' => 'yes',
        ]]);

        $preview = $service->preview(MasterEntityType::Industries, $file, $admin);

        $this->actingAs($admin)
            ->post(route('admin.master-data.excel.confirm', ['type' => 'industries']), [
                'token' => $preview['token'],
            ])
            ->assertRedirect();

        $this->actingAs($admin)
            ->from(route('admin.master-data.index'))
            ->post(route('admin.master-data.excel.confirm', ['type' => 'industries']), [
                'token' => $preview['token'],
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('token');
    }

    public function test_import_summary_reflects_persisted_results(): void
    {
        $admin = $this->makeAdmin();
        $file = $this->makeWorkbook(MasterEntityType::Industries, [[
            'id' => '',
            'name' => 'Excel Summary Industry',
            'description' => '',
            'sort_order' => '9',
            'is_active' => 'yes',
        ]]);

        $preview = app(MasterDataExcelService::class)->preview(MasterEntityType::Industries, $file, $admin);

        $this->actingAs($admin)
            ->post(route('admin.master-data.excel.confirm', ['type' => 'industries']), [
                'token' => $preview['token'],
            ])
            ->assertRedirect()
            ->assertSessionHas('masterDataImportOutcome');

        $outcome = session('masterDataImportOutcome');
        $this->assertSame(1, $outcome['created']);
        $this->assertNotNull(Industry::findByName('Excel Summary Industry'));
    }

    public function test_existing_job_references_remain_intact_after_master_data_import(): void
    {
        $admin = $this->makeAdmin();
        $industry = $this->seedIndustry($admin, 'Referenced Industry');
        $job = Job::factory()->create([
            'industry_id' => $industry->id,
        ]);

        $file = $this->makeWorkbook(MasterEntityType::Industries, [[
            'id' => (string) $industry->id,
            'name' => 'Referenced Industry Renamed',
            'description' => $industry->description ?? '',
            'sort_order' => (string) $industry->sort_order,
            'is_active' => 'yes',
        ]]);

        $preview = app(MasterDataExcelService::class)->preview(MasterEntityType::Industries, $file, $admin);
        app(MasterDataExcelService::class)->confirm(MasterEntityType::Industries, $preview['token'], $admin);

        $this->assertSame($industry->id, $job->fresh()->industry_id);
        $this->assertSame('Referenced Industry Renamed', Industry::find($industry->id)?->name);
    }

    public function test_all_supported_master_entity_types_expose_excel_routes(): void
    {
        $admin = $this->makeAdmin();

        foreach (MasterEntityType::cases() as $type) {
            $this->actingAs($admin)
                ->get(route('admin.master-data.excel.template', ['type' => $type->value]))
                ->assertOk();
        }
    }

    public function test_application_status_import_is_update_only(): void
    {
        app(MasterDataBootstrapper::class)->run();
        $admin = $this->makeAdmin();
        $existing = ApplicationStatusOption::query()->first();
        $this->assertNotNull($existing);

        $file = $this->makeWorkbook(MasterEntityType::ApplicationStatuses, [[
            'id' => (string) $existing->id,
            'code' => $existing->code,
            'name' => $existing->name.' Updated',
            'sort_order' => (string) $existing->sort_order,
            'is_active' => 'yes',
        ]]);

        $preview = app(MasterDataExcelService::class)->preview(MasterEntityType::ApplicationStatuses, $file, $admin);
        $this->assertSame(1, $preview['summary']['update']);
    }

    private function seedIndustry(User $admin, string $name): Industry
    {
        $this->actingAs($admin)
            ->post(route('admin.master-data.store', ['type' => 'industries']), [
                'name' => $name,
                'description' => '',
                'sort_order' => 1,
            ])
            ->assertRedirect();

        $industry = Industry::findByName($name);
        $this->assertNotNull($industry);

        return $industry;
    }

    /**
     * @param  list<array<string, string>>  $rows
     */
    private function makeWorkbook(MasterEntityType $type, array $rows): UploadedFile
    {
        $path = Storage::disk('local')->path('tests/master-import-'.uniqid('', true).'.xlsx');
        MasterDataImportWorkbookFactory::write($type, $rows, $path);

        return new UploadedFile($path, basename($path), null, null, true);
    }
}
