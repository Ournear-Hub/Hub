<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreShowcaseCompanyRequest;
use App\Http\Requests\Admin\UpdateHomepageStatisticsRequest;
use App\Http\Requests\Admin\UpdateShowcaseCompanyRequest;
use App\Models\ShowcaseCompany;
use App\Services\ShowcaseContentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ShowcaseWebsiteContentController extends Controller
{
    public function updateStatistics(UpdateHomepageStatisticsRequest $request, ShowcaseContentService $showcase): RedirectResponse
    {
        $showcase->updateStatistics($request->validated());

        return redirect()
            ->route('admin.website-content.index', ['page' => 'showcase-statistics'])
            ->with('success', 'Homepage statistics saved.');
    }

    public function storeCompany(StoreShowcaseCompanyRequest $request, ShowcaseContentService $showcase): RedirectResponse
    {
        $company = $showcase->createCompany($request->validated());
        if ($request->file('logo')) {
            $showcase->updateCompany($company, ['logo' => $request->file('logo')]);
        } elseif ($request->filled('media_asset_id')) {
            $showcase->updateCompany($company, ['media_asset_id' => $request->integer('media_asset_id')]);
        }

        return back()->with('success', 'Showcase company created.');
    }

    public function updateCompany(
        UpdateShowcaseCompanyRequest $request,
        ShowcaseCompany $showcaseCompany,
        ShowcaseContentService $showcase,
    ): RedirectResponse {
        $showcase->updateCompany($showcaseCompany, $request->validated());

        return back()->with('success', 'Showcase company saved.');
    }

    public function reorderCompanies(Request $request, ShowcaseContentService $showcase): RedirectResponse
    {
        abort_unless($request->user()?->canManageWebsite() === true, 403);

        $request->validate([
            'ordered_ids' => ['required', 'array', 'min:1'],
            'ordered_ids.*' => ['integer', 'exists:showcase_companies,id'],
        ]);

        $showcase->reorderCompanies($request->input('ordered_ids'));

        return back()->with('success', 'Company order updated.');
    }
}
