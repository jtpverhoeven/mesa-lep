<?php

namespace App\Http\Controllers;

use App\Actions\AuditTrail\GetAuditTrail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditTrailController extends Controller
{
    public function index(?string $barcode = null): View
    {
        return view('audit-trail.index', ['initialBarcode' => $barcode ?? '']);
    }

    public function show(Request $request, GetAuditTrail $auditTrail): JsonResponse
    {
        $data = $request->validate(['barcode' => ['required', 'string', 'max:32']]);
        $trail = $auditTrail->handle($data['barcode'], $request->user()->can('projects.view'));

        abort_if($trail === null, 404, 'Foute barcode');

        return response()->json(['data' => $trail]);
    }

    public function print(Request $request, string $barcode, GetAuditTrail $auditTrail): View
    {
        $trail = $auditTrail->handle($barcode, $request->user()->can('projects.view'));

        abort_if($trail === null, 404, 'Foute barcode');

        return view('audit-trail.print', ['trail' => $trail]);
    }
}
