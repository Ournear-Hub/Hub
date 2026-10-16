<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DesignSystemController extends Controller
{
    private const ALLOWED_SECTIONS = [
        'overview',
        'foundation',
        'icons',
        'atoms-button',
        'atoms-icon-button',
        'atoms-input',
        'atoms-textarea',
        'atoms-select',
        'atoms-checkbox',
        'atoms-radio',
        'atoms-switch',
        'atoms-skeleton',
        'atoms-spinner',
        'atoms-progress',
        'atoms-divider',
        'atoms-link',
        'atoms-visually-hidden',
        'atoms-avatar',
        'atoms-chip',
        'atoms-image',
        'atoms-link-button',
        'atoms-card',
        'atoms-container',
        'atoms-stack',
        'atoms-cluster',
        'atoms-aspect-ratio',
        'molecules-form-field',
        'molecules-search-field',
        'molecules-password-field',
        'molecules-file-field',
        'molecules-date-field',
        'molecules-time-field',
        'molecules-date-time-field',
        'molecules-number-field',
        'molecules-email-field',
        'molecules-url-field',
        'molecules-field-group',
        'molecules-input-group',
        'molecules-field-set',
        'molecules-combobox',
        'molecules-multi-select',
        'molecules-segmented-control',
        'molecules-accordion',
        'molecules-tooltip',
        'molecules-popover',
        'molecules-dropdown-menu',
        'molecules-alert',
        'molecules-toast',
        'molecules-empty-state',
        'molecules-data-table',
        'molecules-filter-bar',
        'molecules-breadcrumb',
        'molecules-pagination',
        'molecules-tabs',
        'molecules-timeline',
        'molecules-dialog',
        'molecules-drawer',
        'organisms-detail-header',
        'organisms-detail-section',
        'organisms-document-group-list',
        'organisms-match-comparison-table',
        'organisms-screening-stages-table',
        'organisms-applicant-summary-card',
        'organisms-applicant-resume-overview',
        'organisms-application-timeline',
    ];

    public function show(Request $request): Response
    {
        abort_unless($request->user()?->canManageWebsite() === true, 403);

        $section = (string) ($request->route()?->defaults['section'] ?? 'overview');

        if (! in_array($section, self::ALLOWED_SECTIONS, true)) {
            abort(404);
        }

        return Inertia::render('Admin/DesignSystem/Show', [
            'section' => $section,
        ]);
    }
}
