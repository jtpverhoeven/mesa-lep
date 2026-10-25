<?php

namespace App\Http\Controllers;

use App\Actions\Footers\DeleteFooter;
use App\Actions\Footers\SaveFooter;
use App\Http\Requests\SaveFooterRequest;
use App\Models\DocgenTemplate;
use App\Models\Footer;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class FooterController extends Controller
{
    public function index(): View
    {
        return view('footers.index', ['footers' => Footer::query()->orderBy('name')->orderBy('lang')->orderBy('q')->orderBy('id')->get()]);
    }

    public function create(): View
    {
        return $this->form(new Footer(['lang' => 'nl', 'q' => 0]));
    }

    public function store(SaveFooterRequest $request, SaveFooter $save): RedirectResponse
    {
        $footer = $save->handle(new Footer, $request->validated());

        return to_route('footers.edit', $footer)->with('success', 'Voettekst aangemaakt.');
    }

    public function edit(Footer $footer): View
    {
        return $this->form($footer);
    }

    public function update(SaveFooterRequest $request, Footer $footer, SaveFooter $save): RedirectResponse
    {
        $save->handle($footer, $request->validated());

        return to_route('footers.edit', $footer)->with('success', 'Voettekst opgeslagen.');
    }

    public function destroy(Footer $footer, DeleteFooter $delete): RedirectResponse
    {
        $delete->handle($footer);

        return to_route('footers.index')->with('success', 'Voettekst verwijderd.');
    }

    private function form(Footer $footer): View
    {
        return view('footers.form', [
            'footer' => $footer,
            'folders' => DocgenTemplate::query()->inDisplayOrder()->pluck('folder')->unique(),
        ]);
    }
}
