<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreMediaRequest;
use App\Http\Requests\Admin\UpdateSocialLinksRequest;
use App\Models\MediaAsset;
use App\Services\MediaLibraryService;
use App\Services\SocialLinkService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MediaSocialController extends Controller
{
    public function index(Request $request, SocialLinkService $socials): Response
    {
        $tab = $request->string('tab')->toString() === 'social' ? 'social' : 'media';

        $media = MediaAsset::query()
            ->with(['sections.page', 'items'])
            ->latest()
            ->paginate(12)
            ->withQueryString()
            ->through(fn (MediaAsset $asset) => $asset->toAdminArray());

        return Inertia::render('Admin/MediaSocial/Index', [
            'tab' => $tab,
            'media' => $media,
            'socialLinks' => $socials->adminLinks(),
        ]);
    }

    public function store(StoreMediaRequest $request, MediaLibraryService $library): RedirectResponse
    {
        $library->store($request->file('image'));

        return redirect()
            ->route('admin.media-social.index', ['tab' => 'media'])
            ->with('success', 'Image uploaded.');
    }

    public function destroy(MediaAsset $media, MediaLibraryService $library): RedirectResponse
    {
        $library->delete($media);

        return back()->with('success', 'Image deleted.');
    }

    public function updateSocial(UpdateSocialLinksRequest $request, SocialLinkService $socials): RedirectResponse
    {
        $socials->update($request->validated()['links']);

        return redirect()
            ->route('admin.media-social.index', ['tab' => 'social'])
            ->with('success', 'Social links saved.');
    }
}
