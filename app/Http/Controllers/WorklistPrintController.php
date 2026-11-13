<?php

namespace App\Http\Controllers;

use App\Actions\Worklists\PrintWorklists;
use App\Http\Requests\PrintWorklistsRequest;
use App\Models\Worklist;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\View\View;

class WorklistPrintController extends Controller
{
    public function index(): View
    {
        return view('worklists.print', [
            'worklists' => Worklist::query()->orderBy('title')->orderBy('id')->get(['id', 'title']),
        ]);
    }

    public function pdf(PrintWorklistsRequest $request, PrintWorklists $print): Response|RedirectResponse
    {
        $worklist = $request->input('worklist') === 'all' ? null : Worklist::query()->findOrFail($request->input('worklist'));
        $file = $print->handle($worklist, $request->input('date'));

        if ($file === null) {
            return to_route('worklists.print')->withErrors(['date' => 'Geen monsters gevonden voor deze inzetdatum.']);
        }

        return response($file['content'], 200, [
            'Content-Type' => $file['mime'],
            'Content-Disposition' => ($file['mime'] === 'application/pdf' ? 'inline' : 'attachment').'; filename="'.$file['filename'].'"',
        ]);
    }
}
