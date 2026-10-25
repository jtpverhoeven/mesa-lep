<?php

namespace App\Http\Controllers;

use App\Actions\DocgenTemplates\DeleteDocgenTemplate;
use App\Actions\DocgenTemplates\SaveDocgenTemplate;
use App\Http\Requests\SaveDocgenTemplateRequest;
use App\Models\DocgenTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class DocgenTemplateController extends Controller
{
    public function index(): View
    {
        return view('docgen-templates.index', [
            'templates' => DocgenTemplate::query()->withCount('associations')->inDisplayOrder()->get(),
        ]);
    }

    public function create(): View
    {
        return view('docgen-templates.form', ['template' => new DocgenTemplate(['active' => true])]);
    }

    public function store(SaveDocgenTemplateRequest $request, SaveDocgenTemplate $save): RedirectResponse
    {
        $template = $save->handle(new DocgenTemplate, $request->validated());

        return to_route('docgen-templates.edit', $template)->with('success', 'Rapportsjabloon aangemaakt.');
    }

    public function edit(DocgenTemplate $docgenTemplate): View
    {
        return view('docgen-templates.form', ['template' => $docgenTemplate]);
    }

    public function update(SaveDocgenTemplateRequest $request, DocgenTemplate $docgenTemplate, SaveDocgenTemplate $save): RedirectResponse
    {
        $save->handle($docgenTemplate, $request->validated());

        return to_route('docgen-templates.edit', $docgenTemplate)->with('success', 'Rapportsjabloon opgeslagen.');
    }

    public function destroy(DocgenTemplate $docgenTemplate, DeleteDocgenTemplate $delete): RedirectResponse
    {
        $delete->handle($docgenTemplate);

        return to_route('docgen-templates.index')->with('success', 'Rapportsjabloon en analyse-associaties verwijderd.');
    }
}
