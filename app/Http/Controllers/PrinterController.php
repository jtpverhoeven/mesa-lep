<?php

namespace App\Http\Controllers;

use App\Actions\Printers\CreatePrinter;
use App\Actions\Printers\DeletePrinter;
use App\Actions\Printers\UpdatePrinter;
use App\Http\Requests\StorePrinterRequest;
use App\Http\Requests\UpdatePrinterRequest;
use App\Models\Printer;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PrinterController extends Controller
{
    public function index(): View
    {
        return view('printers.index', [
            'printers' => Printer::query()->orderBy('name')->orderBy('id')->get(),
            'types' => Printer::TYPES,
        ]);
    }

    public function create(): View
    {
        return view('printers.create', ['printer' => new Printer, 'types' => Printer::TYPES]);
    }

    public function store(StorePrinterRequest $request, CreatePrinter $createPrinter): RedirectResponse
    {
        $printer = $createPrinter->handle($request->validated());

        return to_route('printers.index')->with('success', 'Printer "'.$printer->name.'" is aangemaakt.');
    }

    public function edit(Printer $printer): View
    {
        return view('printers.edit', ['printer' => $printer, 'types' => Printer::TYPES]);
    }

    public function update(UpdatePrinterRequest $request, Printer $printer, UpdatePrinter $updatePrinter): RedirectResponse
    {
        $printer = $updatePrinter->handle($printer, $request->validated());

        return to_route('printers.edit', $printer)->with('success', 'Printer "'.$printer->name.'" is bijgewerkt.');
    }

    public function destroy(Printer $printer, DeletePrinter $deletePrinter): RedirectResponse
    {
        $name = $printer->name;
        $deletePrinter->handle($printer);

        return to_route('printers.index')->with('success', 'Printer "'.$name.'" is verwijderd.');
    }
}
