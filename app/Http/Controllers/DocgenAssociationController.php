<?php

namespace App\Http\Controllers;

use App\Actions\DocgenTemplates\SyncDocgenAssociations;
use App\Http\Requests\SyncDocgenAssociationsRequest;
use App\Models\Assay;
use App\Models\DocgenTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DocgenAssociationController extends Controller
{
    public function edit(Request $request, DocgenTemplate $docgenTemplate): View
    {
        $showHidden = $request->boolean('show_hidden');

        return view('docgen-templates.associations', [
            'template' => $docgenTemplate,
            'showHidden' => $showHidden,
            'assays' => Assay::query()->when(! $showHidden, fn ($query) => $query->where('active', 1))
                ->orderBy('name')->orderBy('id')->get(['id', 'original_id', 'name', 'active']),
            'selected' => $docgenTemplate->associations()->pluck('assay_id')->all(),
            'templates' => DocgenTemplate::query()->whereKeyNot($docgenTemplate->id)->inDisplayOrder()->get(),
        ]);
    }

    public function update(SyncDocgenAssociationsRequest $request, DocgenTemplate $docgenTemplate, SyncDocgenAssociations $sync): RedirectResponse
    {
        $sync->handle($docgenTemplate, $request->validated('assay_ids'), $request->boolean('show_hidden'));

        return to_route('docgen-associations.edit', [$docgenTemplate, 'show_hidden' => $request->boolean('show_hidden')])
            ->with('success', 'Analyse-associaties opgeslagen.');
    }

    public function copy(Request $request, DocgenTemplate $docgenTemplate, SyncDocgenAssociations $sync): RedirectResponse
    {
        $data = $request->validate([
            'source_template' => ['required', 'integer', Rule::exists('docgentemplates', 'id'), Rule::notIn([$docgenTemplate->id])],
        ]);
        $source = DocgenTemplate::query()->findOrFail($data['source_template']);
        $sync->copy($docgenTemplate, $source);

        return to_route('docgen-associations.edit', $docgenTemplate)->with('success', 'Analyse-associaties gekopieerd.');
    }
}
