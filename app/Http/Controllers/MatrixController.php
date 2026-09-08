<?php

namespace App\Http\Controllers;

use App\Actions\Matrix\CreateMatrix;
use App\Actions\Matrix\DeleteMatrix;
use App\Actions\Matrix\UpdateMatrix;
use App\Http\Requests\StoreMatrixRequest;
use App\Http\Requests\UpdateMatrixRequest;
use App\Models\Matrix;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class MatrixController extends Controller
{
    public function index(): View
    {
        return view('matrices.index', [
            'matrices' => Matrix::where('active', 1)->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('matrices.create');
    }

    public function store(StoreMatrixRequest $request, CreateMatrix $createMatrix): RedirectResponse
    {
        $matrix = $createMatrix->handle($request->validated());

        return to_route('matrices.index')->with('success', 'Matrix "'.$matrix->name.'" is aangemaakt.');
    }

    public function edit(Matrix $matrix): View
    {
        return view('matrices.edit', ['matrix' => $matrix]);
    }

    public function update(
        UpdateMatrixRequest $request,
        Matrix $matrix,
        UpdateMatrix $updateMatrix,
    ): RedirectResponse {
        $matrix = $updateMatrix->handle($matrix, $request->validated());

        return to_route('matrices.edit', $matrix)->with('success', 'Matrix "'.$matrix->name.'" is bijgewerkt.');
    }

    public function destroy(Matrix $matrix, DeleteMatrix $deleteMatrix): RedirectResponse
    {
        $deleteMatrix->handle($matrix);

        return to_route('matrices.index')->with('success', 'Matrix "'.$matrix->name.'" is verwijderd.');
    }
}