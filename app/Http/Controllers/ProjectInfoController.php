<?php

namespace App\Http\Controllers;

use App\ClientPortal\ClientPortalService;
use App\Models\Project;
use App\Support\AccountImages;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use UnexpectedValueException;

class ProjectInfoController extends Controller
{
    public function portalStatus(Request $request, Project $project, ClientPortalService $portal): JsonResponse
    {
        $this->authorizeViewing($request);
        $response = $portal->checkClientIsPortalUser($project->client);

        if (! $response->successful()) {
            return response()->json(['message' => 'Portaalstatus kon niet worden opgehaald.'], 502);
        }

        $users = $response->json('users');
        if (! is_numeric($users) || (int) $users < 0) {
            throw new UnexpectedValueException('The client portal returned an invalid user count.');
        }

        return response()->json(['active' => (int) $users > 0]);
    }

    public function authorizedAvatar(Request $request, Project $project): StreamedResponse
    {
        $this->authorizeViewing($request);
        abort_unless((int) $project->auth_status === 1, 404);

        $project->load('authorizedBy.profile');
        $path = $project->authorizedBy ? AccountImages::path($project->authorizedBy, 'avatar') : null;
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path, null, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function orderForm(Request $request, Project $project, ClientPortalService $portal): Response
    {
        $this->authorizeViewing($request);
        abort_unless($project->portal_id !== null, 404);

        $response = $portal->getOrderForm($project->portal_id)->throw();
        $status = $response->json('status');
        if ((string) $status === '404' || (string) $status === '422') {
            abort(404, 'Geen orderformulier beschikbaar.');
        }

        $pdf = base64_decode((string) $response->json('pdf'), true);
        if ($pdf === false || ! str_starts_with($pdf, '%PDF-')) {
            throw new UnexpectedValueException('The client portal returned an invalid order form.');
        }

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="orderformulier.pdf"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function authorizeViewing(Request $request): void
    {
        abort_unless($request->user()->can('projects.view') || $request->user()->can('samples.view'), 403);
    }
}
