<?php

namespace App\Services;

use App\Models\JobSearchEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class JobSearchAnalyticsService
{
    private const KEYWORD_MAX = 120;

    private const LOCATION_MAX = 120;

    public function recordFromRequest(Request $request): void
    {
        if (! Schema::hasTable('job_search_events')) {
            return;
        }

        $keyword = $this->normalizeTerm((string) $request->query('keyword', ''));
        $location = $this->normalizeTerm((string) $request->query('location', ''));

        if ($keyword === '' && $location === '') {
            return;
        }

        if (! $this->isValidSearchTerm($keyword) && ! $this->isValidSearchTerm($location)) {
            return;
        }

        JobSearchEvent::query()->create([
            'keyword' => $keyword,
            'location' => $location !== '' ? $location : null,
            'weight' => 1,
            'searched_at' => now(),
        ]);

        Cache::forget('homepage.popular_job_searches');
    }

    public function normalizeTerm(string $value): string
    {
        $trimmed = trim(preg_replace('/\s+/u', ' ', $value) ?? '');

        return mb_substr($trimmed, 0, self::KEYWORD_MAX);
    }

    private function isValidSearchTerm(string $term): bool
    {
        if ($term === '') {
            return false;
        }

        if (mb_strlen($term) < 2) {
            return false;
        }

        if (preg_match('/^[%_]+$/', $term)) {
            return false;
        }

        return true;
    }
}
