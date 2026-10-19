<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePublicPageImageRequest;
use App\Http\Requests\Admin\UpdatePublicPageImageRequest;
use App\Models\PublicPageImage;
use App\Services\PublicPageImageService;
use App\Support\PublicPageImageRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Inertia\Inertia;
use Inertia\Response;

class PublicPageImageController extends Controller
{
    public function index(Request $request, PublicPageImageService $images): Response
    {
        abort_unless($request->user()?->canManageWebsite() === true, 403);

        return Inertia::render('Admin/PageImages/Index', [
            'images' => $images->adminRows(),
        ]);
    }

    public function create(Request $request): Response
    {
        abort_unless($request->user()?->canManageWebsite() === true, 403);

        return Inertia::render('Admin/PageImages/Form', [
            'slots' => PublicPageImageRegistry::slots(),
            'image' => null,
            'selected' => [
                'page_slug' => $request->string('page')->toString(),
                'slot_key' => $request->string('slot')->toString(),
            ],
        ]);
    }

    public function store(StorePublicPageImageRequest $request, PublicPageImageService $images): RedirectResponse
    {
        $file = $request->file('image');
        abort_unless($file instanceof UploadedFile, 422);

        $images->store(
            $request->string('page_slug')->toString(),
            $request->string('slot_key')->toString(),
            $request->string('title')->toString(),
            $request->string('alt_text')->toString(),
            $request->boolean('is_active'),
            $file,
        );

        return redirect()
            ->route('admin.page-images.index')
            ->with('success', 'Page image saved.');
    }

    public function edit(Request $request, PublicPageImage $pageImage): Response
    {
        abort_unless($request->user()?->canManageWebsite() === true, 403);
        $pageImage->load('media');

        return Inertia::render('Admin/PageImages/Form', [
            'slots' => PublicPageImageRegistry::slots(),
            'image' => [
                'id' => $pageImage->id,
                'page_slug' => $pageImage->page_slug,
                'slot_key' => $pageImage->slot_key,
                'slot_label' => PublicPageImageRegistry::label($pageImage->page_slug, $pageImage->slot_key),
                'title' => $pageImage->title,
                'alt_text' => $pageImage->alt_text,
                'is_active' => $pageImage->is_active,
                'url' => $pageImage->media?->url(),
                'filename' => $pageImage->media?->original_name,
            ],
            'selected' => [
                'page_slug' => $pageImage->page_slug,
                'slot_key' => $pageImage->slot_key,
            ],
        ]);
    }

    public function update(UpdatePublicPageImageRequest $request, PublicPageImage $pageImage, PublicPageImageService $images): RedirectResponse
    {
        $images->update(
            $pageImage,
            $request->string('title')->toString(),
            $request->string('alt_text')->toString(),
            $request->boolean('is_active'),
            $request->file('image'),
        );

        return redirect()
            ->route('admin.page-images.index')
            ->with('success', 'Page image updated.');
    }
}
