<?php

namespace App\Http\Controllers;

use App\Actions\Billing\CreateBillingOverview;
use App\Actions\Billing\ExportBillingOverview;
use App\Actions\Clients\SearchClients;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BillingController extends Controller
{
    public function index(): View
    {
        return view('billing.index');
    }

    public function overview(Request $request, CreateBillingOverview $overview): JsonResponse
    {
        $data = $request->validate([
            'projects' => ['nullable', 'array', 'max:500'],
            'projects.*' => ['integer'],
            'barcode' => ['nullable', 'string', 'max:128'],
            'billing_status' => ['nullable', 'string'],
            'client' => ['nullable', 'integer'],
            'date_from' => ['nullable', 'string', 'max:32'],
            'date_to' => ['nullable', 'string', 'max:32'],
        ]);

        return response()->json($overview->handle(
            $data['projects'] ?? [],
            trim($data['barcode'] ?? ''),
            (string) ($data['billing_status'] ?? '0'),
            isset($data['client']) ? (int) $data['client'] : null,
            $data['date_from'] ?? null,
            $data['date_to'] ?? null,
        ));
    }

    public function clients(Request $request, SearchClients $searchClients): JsonResponse
    {
        return response()->json(['data' => $searchClients->handle((string) $request->query('query'))]);
    }

    /**
     * Port of legacy billingController::markProjectsBilled.
     */
    public function markBilled(Request $request): JsonResponse
    {
        $data = $request->validate([
            'projects' => ['nullable', 'array', 'max:500'],
            'projects.*' => ['integer'],
        ]);
        $projectIds = array_values(array_unique(array_filter(array_map('intval', $data['projects'] ?? []), fn (int $id): bool => $id > 0)));

        if ($projectIds === []) {
            return response()->json(['status' => CreateBillingOverview::STATUS_NO_PROJECTS]);
        }

        return response()->json([
            'status' => CreateBillingOverview::STATUS_OK,
            'updated' => Project::query()->whereKey($projectIds)->update(['billed' => 1]),
        ]);
    }

    /**
     * Port of legacy billingController::setProjectBilled.
     */
    public function setBilled(Request $request): JsonResponse
    {
        $projectId = (int) $request->input('project');
        $billed = (string) $request->input('billed');

        if ($projectId <= 0 || ! in_array($billed, ['0', '1'], true)) {
            return response()->json(['status' => 'invalid_input'], 422);
        }

        Project::query()->whereKey($projectId)->update(['billed' => (int) $billed]);

        return response()->json(['status' => CreateBillingOverview::STATUS_OK, 'project' => $projectId, 'billed' => (int) $billed]);
    }

    public function export(Request $request, CreateBillingOverview $overview, ExportBillingOverview $export): BinaryFileResponse
    {
        $data = $request->validate([
            'projects' => ['required', 'array', 'max:500'],
            'projects.*' => ['integer'],
        ]);

        $result = $overview->handle($data['projects'], billingStatus: 'all');
        abort_if($result['status'] === CreateBillingOverview::STATUS_CLIENT_MISMATCH, 422, 'De geselecteerde projecten zijn niet van dezelfde klant');
        abort_if($result['projects'] === [], 422, 'Geen projecten geselecteerd');

        $path = tempnam(sys_get_temp_dir(), 'billing');
        $export->write($result, $path);

        return response()->download($path, $export->filename($result), [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend();
    }
}
