<?php

namespace App\Services;

use App\Models\EmployerProfile;
use App\Models\HomepageStatistic;
use App\Models\MediaAsset;
use App\Models\ShowcaseCompany;
use App\Support\ShowcaseSchema;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ShowcaseContentService
{
    public function __construct(private MediaLibraryService $mediaLibrary) {}

    public function tablesReady(): bool
    {
        return ShowcaseSchema::isReady();
    }

    public function statistics(): HomepageStatistic
    {
        if (! $this->tablesReady()) {
            return $this->defaultStatisticsModel();
        }

        return HomepageStatistic::query()->firstOrCreate(
            ['lock_key' => 'default'],
            [
                'registered_companies' => 0,
                'talent_pool_companies' => 0,
                'staffing_companies' => 0,
                'registered_users' => 0,
            ],
        );
    }

    private function defaultStatisticsModel(): HomepageStatistic
    {
        return new HomepageStatistic([
            'lock_key' => 'default',
            'registered_companies' => 0,
            'talent_pool_companies' => 0,
            'staffing_companies' => 0,
            'registered_users' => 0,
        ]);
    }

    private function assertTablesReady(): void
    {
        if (! $this->tablesReady()) {
            throw ValidationException::withMessages([
                'migration' => 'Showcase tables are missing. Run php artisan migrate from the project root, then retry.',
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function updateStatistics(array $payload): HomepageStatistic
    {
        $this->assertTablesReady();
        $row = $this->statistics();

        foreach ([
            'registered_companies',
            'talent_pool_companies',
            'staffing_companies',
            'registered_users',
        ] as $key) {
            if (array_key_exists($key, $payload)) {
                $row->{$key} = (int) $payload[$key];
            }
        }

        $row->save();

        return $row->refresh();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function adminCompanies(?string $surface = null): array
    {
        if (! $this->tablesReady()) {
            return [];
        }

        $query = ShowcaseCompany::query()->with('mediaAsset')->orderBy('sort_order')->orderBy('name');

        return $query->get()->map(fn (ShowcaseCompany $company) => $company->toAdminArray())->all();
    }

    /**
     * @return list<array{name: string, logo_url: string|null}>
     */
    public function publicCompaniesFor(string $surface): array
    {
        if (! $this->tablesReady()) {
            return [];
        }

        $column = match ($surface) {
            'home' => 'show_on_home',
            'talent_pool' => 'show_on_talent_pool',
            'staffing' => 'show_on_staffing',
            default => throw new \InvalidArgumentException('Unknown showcase surface.'),
        };

        return ShowcaseCompany::query()
            ->with('mediaAsset')
            ->where('is_active', true)
            ->where($column, true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn (ShowcaseCompany $company) => $company->toPublicArray())
            ->all();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function createCompany(array $payload): ShowcaseCompany
    {
        $this->assertTablesReady();
        $maxOrder = ShowcaseCompany::query()->max('sort_order');
        $sortOrder = (int) ($payload['sort_order'] ?? ((int) $maxOrder + 1));

        return ShowcaseCompany::query()->create([
            'name' => trim((string) $payload['name']),
            'employer_profile_id' => $this->employerProfileId($payload),
            'media_asset_id' => $payload['media_asset_id'] ?? null,
            'is_active' => (bool) ($payload['is_active'] ?? true),
            'sort_order' => $sortOrder,
            'show_on_home' => (bool) ($payload['show_on_home'] ?? false),
            'show_on_talent_pool' => (bool) ($payload['show_on_talent_pool'] ?? false),
            'show_on_staffing' => (bool) ($payload['show_on_staffing'] ?? false),
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function updateCompany(ShowcaseCompany $company, array $payload): ShowcaseCompany
    {
        $this->assertTablesReady();
        if (array_key_exists('name', $payload)) {
            $company->name = trim((string) $payload['name']);
        }

        if (array_key_exists('employer_profile_id', $payload)) {
            $payload['ignore_showcase_id'] = $company->id;
            $company->employer_profile_id = $this->employerProfileId($payload);
        }

        foreach (['is_active', 'show_on_home', 'show_on_talent_pool', 'show_on_staffing'] as $flag) {
            if (array_key_exists($flag, $payload)) {
                $company->{$flag} = (bool) $payload[$flag];
            }
        }

        if (array_key_exists('sort_order', $payload)) {
            $company->sort_order = (int) $payload['sort_order'];
        }

        if (array_key_exists('remove_logo', $payload) && $payload['remove_logo'] === true) {
            $company->media_asset_id = null;
        } elseif (array_key_exists('media_asset_id', $payload)) {
            $mediaId = $payload['media_asset_id'];
            $company->media_asset_id = is_numeric($mediaId) && (int) $mediaId > 0 ? (int) $mediaId : null;
        }

        if (($payload['logo'] ?? null) instanceof UploadedFile) {
            $asset = $this->mediaLibrary->store($payload['logo']);
            $company->media_asset_id = $asset->id;
        }

        $company->save();

        return $company->refresh()->load('mediaAsset');
    }

    /**
     * @param  list<int>  $orderedIds
     */
    public function reorderCompanies(array $orderedIds): void
    {
        $this->assertTablesReady();
        DB::transaction(function () use ($orderedIds): void {
            foreach (array_values($orderedIds) as $index => $id) {
                ShowcaseCompany::query()->whereKey($id)->update(['sort_order' => $index]);
            }
        });
    }

    public function assignMediaFromLibrary(ShowcaseCompany $company, int $mediaId, string $field = 'logo'): ShowcaseCompany
    {
        $asset = MediaAsset::query()->findOrFail($mediaId);
        if (! str_starts_with((string) $asset->mime_type, 'image/')) {
            throw ValidationException::withMessages([
                $field => 'The selected media file must be an image.',
            ]);
        }

        $company->media_asset_id = $asset->id;
        $company->save();

        return $company->refresh()->load('mediaAsset');
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function employerProfileId(array $payload): ?int
    {
        $id = (int) ($payload['employer_profile_id'] ?? 0);
        if ($id <= 0) {
            return null;
        }

        if (! EmployerProfile::query()->whereKey($id)->exists()) {
            throw ValidationException::withMessages([
                'employer_profile_id' => 'Select an existing company record.',
            ]);
        }

        $taken = ShowcaseCompany::query()
            ->where('employer_profile_id', $id)
            ->when(isset($payload['ignore_showcase_id']), fn ($query) => $query->whereKeyNot((int) $payload['ignore_showcase_id']))
            ->exists();

        if ($taken) {
            throw ValidationException::withMessages([
                'employer_profile_id' => 'That company is already linked to a showcase card. Edit the existing card instead of creating another one.',
            ]);
        }

        return $id;
    }
}
