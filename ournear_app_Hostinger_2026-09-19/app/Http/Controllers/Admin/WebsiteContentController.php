<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ContactEnquiryStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ResetWebsiteContentRequest;
use App\Http\Requests\Admin\StoreEnquiryNoteRequest;
use App\Http\Requests\Admin\UpdateContactEnquiryStatusRequest;
use App\Http\Requests\Admin\UpdateWebsiteContentRequest;
use App\Models\ContactEnquiry;
use App\Models\MediaAsset;
use App\Models\WebsiteSection;
use App\Services\WebsiteContentService;
use App\Support\AppendsEnquiryNotes;
use App\Support\ContactEnquiryQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WebsiteContentController extends Controller
{
    use AppendsEnquiryNotes;

    public function index(Request $request, ContactEnquiryQuery $enquiries, WebsiteContentService $content): Response
    {
        abort_unless($request->user()?->canManageWebsite() === true, 403);

        $content->ensureSeeded();

        $allowed = collect($content->pageTabs())->pluck('slug');
        $pageSlug = $request->string('page')->toString();
        if (! $allowed->contains($pageSlug)) {
            $pageSlug = 'home';
        }

        $cmsPage = $content->adminPage($pageSlug);
        $sectionKeys = collect($cmsPage['sections'])->pluck('key');
        $sectionKey = $request->string('section')->toString();
        if (! $sectionKeys->contains($sectionKey)) {
            $sectionKey = $sectionKeys->first() ?? 'hero';
        }

        $page = $enquiries->adminIndex($request)->through(fn (ContactEnquiry $enquiry) => $enquiry->toAdminArray());

        $selected = null;
        $selectedId = $request->integer('enquiry');
        if ($selectedId > 0) {
            $selected = ContactEnquiry::query()->find($selectedId);
            $pageSlug = 'contact';
            $cmsPage = $content->adminPage($pageSlug);
            $sectionKeys = collect($cmsPage['sections'])->pluck('key');
            if (! $sectionKeys->contains($sectionKey)) {
                $sectionKey = $sectionKeys->first() ?? 'hero';
            }
        }

        return Inertia::render('Admin/WebsiteContent/Index', [
            'tabs' => $content->pageTabs(),
            'cmsPage' => $cmsPage,
            'contentVersion' => WebsiteSection::query()->max('updated_at'),
            'activePage' => $pageSlug,
            'activeSection' => $sectionKey,
            'enquiries' => $page,
            'selected' => $selected?->toAdminArray(),
            'filters' => [
                'search' => $request->string('search')->toString(),
                'status' => $request->string('status')->toString(),
                'enquiry' => $selectedId > 0 ? (string) $selectedId : '',
                'page' => $pageSlug,
                'section' => $sectionKey,
            ],
            'statuses' => array_map(fn (ContactEnquiryStatus $status) => $status->value, ContactEnquiryStatus::cases()),
            'mediaOptions' => MediaAsset::query()
                ->latest()
                ->get()
                ->map(fn (MediaAsset $asset) => $asset->toPickerArray())
                ->values()
                ->all(),
        ]);
    }

    public function update(UpdateWebsiteContentRequest $request, string $page, WebsiteContentService $content): RedirectResponse
    {
        $this->assertPageSlug($page, $content);
        $content->updatePage($page, $request->validated());

        return back()->with('success', 'Website content saved.');
    }

    public function reset(ResetWebsiteContentRequest $request, string $page, WebsiteContentService $content): RedirectResponse
    {
        $this->assertPageSlug($page, $content);
        $content->resetToDefaults();

        return redirect()
            ->route('admin.website-content.index', ['page' => $page])
            ->with('success', 'Website content restored to the OurNear Talent defaults.');
    }

    private function assertPageSlug(string $page, WebsiteContentService $content): void
    {
        $allowed = collect($content->pageTabs())->pluck('slug');
        abort_unless($allowed->contains($page), 404);
    }

    public function updateContactStatus(UpdateContactEnquiryStatusRequest $request, ContactEnquiry $contactEnquiry): RedirectResponse
    {
        $contactEnquiry->update([
            'status' => $request->enum('status', ContactEnquiryStatus::class),
        ]);

        return back()->with('success', 'Contact enquiry status updated.');
    }

    public function storeContactNote(StoreEnquiryNoteRequest $request, ContactEnquiry $contactEnquiry): RedirectResponse
    {
        $contactEnquiry->internal_notes = $this->appendNote(
            $contactEnquiry->notes(),
            $request->string('note')->toString(),
            $request->user()
        );
        $contactEnquiry->save();

        return back()->with('success', 'Internal note added.');
    }
}
