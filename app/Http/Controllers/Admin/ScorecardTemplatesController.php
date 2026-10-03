<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreScorecardTemplateRequest;
use App\Http\Requests\Admin\UpdateScorecardTemplateRequest;
use App\Models\ScorecardTemplate;
use App\Services\ScorecardService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ScorecardTemplatesController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', ScorecardTemplate::class);

        return Inertia::render('Admin/ScorecardTemplates/Index', [
            'templates' => ScorecardTemplate::query()
                ->orderBy('name')
                ->get()
                ->map(fn (ScorecardTemplate $template) => $template->toAdminArray())
                ->all(),
            'can_manage' => $request->user()?->can('create', ScorecardTemplate::class) === true,
        ]);
    }

    public function store(StoreScorecardTemplateRequest $request, ScorecardService $scorecards): RedirectResponse
    {
        $scorecards->saveTemplate(null, $request->validated(), $request->user());

        return back()->with('success', 'Scorecard template created.');
    }

    public function update(
        UpdateScorecardTemplateRequest $request,
        ScorecardTemplate $scorecardTemplate,
        ScorecardService $scorecards,
    ): RedirectResponse {
        $scorecards->saveTemplate($scorecardTemplate, $request->validated(), $request->user());

        return back()->with('success', 'Scorecard template updated.');
    }
}
