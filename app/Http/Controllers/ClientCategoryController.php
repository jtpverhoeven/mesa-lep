<?php

namespace App\Http\Controllers;

use App\Actions\ClientCategories\CreateClientCategory;
use App\Actions\ClientCategories\DeleteClientCategory;
use App\Http\Requests\SaveClientCategoryRequest;
use App\Models\ClientCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClientCategoryController extends Controller
{
    public function index(): View
    {
        return view('client-categories.index', [
            'categories' => ClientCategory::withCount('activeClients')->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('client-categories.create');
    }

    public function store(
        SaveClientCategoryRequest $request,
        CreateClientCategory $createClientCategory,
    ): RedirectResponse|JsonResponse {
        $category = $createClientCategory->handle($request->validated())->loadCount('activeClients');

        if ($request->expectsJson()) {
            return response()->json(['category' => $category], 201);
        }

        return to_route('client-categories.index')->with('success', 'Klantcategorie "'.$category->name.'" is aangemaakt.');
    }

    public function destroy(
        Request $request,
        ClientCategory $clientCategory,
        DeleteClientCategory $deleteClientCategory,
    ): RedirectResponse|JsonResponse {
        $deleteClientCategory->handle($clientCategory);

        if ($request->expectsJson()) {
            return response()->json(status: 204);
        }

        return to_route('client-categories.index')->with('success', 'Klantcategorie is verwijderd.');
    }
}