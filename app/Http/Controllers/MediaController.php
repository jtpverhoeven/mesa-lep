<?php

namespace App\Http\Controllers;

use App\Actions\Media\CreateMedia;
use App\Actions\Media\DeleteMedia;
use App\Actions\Media\UpdateMedia;
use App\Http\Requests\StoreMediaRequest;
use App\Http\Requests\UpdateMediaRequest;
use App\Models\Media;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class MediaController extends Controller
{
    public function index(): View
    {
        return view('media.index', [
            'media' => Media::where('active', 1)->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('media.create', [
            'type' => request()->integer('type') === 3 ? 3 : 1,
        ]);
    }

    public function store(StoreMediaRequest $request, CreateMedia $createMedia): RedirectResponse
    {
        $media = $createMedia->handle($request->validated());

        return to_route('media.index')->with('success', $this->label($media).' "'.$media->name.'" is aangemaakt.');
    }

    public function edit(Media $media): View
    {
        return view('media.edit', ['media' => $media]);
    }

    public function update(
        UpdateMediaRequest $request,
        Media $media,
        UpdateMedia $updateMedia,
    ): RedirectResponse {
        $media = $updateMedia->handle($media, $request->validated());

        return to_route('media.edit', $media)->with('success', $this->label($media).' "'.$media->name.'" is bijgewerkt.');
    }

    public function destroy(Media $media, DeleteMedia $deleteMedia): RedirectResponse
    {
        $deleteMedia->handle($media);

        return to_route('media.index')->with('success', $this->label($media).' "'.$media->name.'" is verwijderd.');
    }

    private function label(Media $media): string
    {
        return (int) $media->type === 3 ? 'Materiaal' : 'Media';
    }
}