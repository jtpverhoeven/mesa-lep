<?php

namespace App\Http\Controllers;

use App\Actions\AssuranceForms\UpdateConfirmationAssuranceExplanation;
use App\Actions\Confirmations\AddConfirmationContender;
use App\Actions\Confirmations\GetConfirmation;
use App\Actions\Confirmations\RemoveConfirmationContender;
use App\Actions\Confirmations\SetConfirmationDecision;
use App\Actions\Confirmations\SetConfirmationSupportMedium;
use App\Actions\Confirmations\UpdateConfirmationMetadata;
use App\Actions\Confirmations\UpdateConfirmationNote;
use App\Actions\Confirmations\UpdateConfirmationTrackValue;
use App\Events\ConfirmationUpdated;
use App\Http\Requests\Confirmations\AddConfirmationContenderRequest;
use App\Http\Requests\Confirmations\RemoveConfirmationContenderRequest;
use App\Http\Requests\Confirmations\SetConfirmationDecisionRequest;
use App\Http\Requests\Confirmations\UpdateConfirmationAssuranceExplanationRequest;
use App\Http\Requests\Confirmations\UpdateConfirmationMetadataRequest;
use App\Http\Requests\Confirmations\UpdateConfirmationNoteRequest;
use App\Http\Requests\Confirmations\UpdateConfirmationSupportRequest;
use App\Http\Requests\Confirmations\UpdateConfirmationTrackRequest;
use App\Http\Resources\ConfirmationResource;
use App\Models\SampleAnalysis;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConfirmationController extends Controller
{
    public function show(Request $request, SampleAnalysis $sampleAnalysis, GetConfirmation $get): JsonResponse
    {
        $scope = $request->validate([
            'df' => ['sometimes', 'string', 'max:32', 'regex:/^(global|\d+(?:\.\d+)?)$/'],
            'rep' => ['sometimes', 'integer', 'min:0'],
        ]);

        return (new ConfirmationResource($get->handle(
            $sampleAnalysis,
            $scope['df'] ?? null,
            (int) ($scope['rep'] ?? 0),
        )))->response();
    }

    public function decision(
        SetConfirmationDecisionRequest $request,
        SampleAnalysis $sampleAnalysis,
        SetConfirmationDecision $setDecision,
        GetConfirmation $get,
    ): JsonResponse {
        $analysis = $setDecision->handle($sampleAnalysis, $request->validated('decision'));

        return $this->updated($analysis, $get);
    }

    public function track(
        UpdateConfirmationTrackRequest $request,
        SampleAnalysis $sampleAnalysis,
        UpdateConfirmationTrackValue $update,
        GetConfirmation $get,
    ): JsonResponse {
        $data = $request->validated();
        $analysis = $update->handle($sampleAnalysis, $data['df'], $data['rep'], $data['contender'], $data['step'], $data['value'] ?? '');

        return $this->updated($analysis, $get, $data['df'], $data['rep']);
    }

    public function metadata(
        UpdateConfirmationMetadataRequest $request,
        SampleAnalysis $sampleAnalysis,
        UpdateConfirmationMetadata $update,
        GetConfirmation $get,
    ): JsonResponse {
        $data = $request->validated();
        $analysis = $update->handle($sampleAnalysis, $data['df'], $data['rep'], $data['key'], $data['value'] ?? null);

        return $this->updated($analysis, $get, $data['df'], $data['rep']);
    }

    public function assuranceExplanation(
        UpdateConfirmationAssuranceExplanationRequest $request,
        SampleAnalysis $sampleAnalysis,
        UpdateConfirmationAssuranceExplanation $update,
        GetConfirmation $get,
    ): JsonResponse {
        $data = $request->validated();
        $analysis = $update->handle($sampleAnalysis, $data['df'], $data['rep'], $data['key'], (string) ($data['explanation'] ?? ''));

        return $this->updated($analysis, $get, $data['df'], $data['rep']);
    }

    public function support(
        UpdateConfirmationSupportRequest $request,
        SampleAnalysis $sampleAnalysis,
        SetConfirmationSupportMedium $update,
        GetConfirmation $get,
    ): JsonResponse {
        $data = $request->validated();
        $analysis = $update->handle($sampleAnalysis, $data['df'], $data['rep'], $data['media_id'], (bool) $data['active']);

        return $this->updated($analysis, $get, $data['df'], $data['rep']);
    }

    public function addContender(
        AddConfirmationContenderRequest $request,
        SampleAnalysis $sampleAnalysis,
        AddConfirmationContender $add,
        GetConfirmation $get,
    ): JsonResponse {
        $data = $request->validated();
        $analysis = $add->handle($sampleAnalysis, $data['df'], $data['rep']);

        return $this->updated($analysis, $get, $data['df'], $data['rep']);
    }

    public function removeContender(
        RemoveConfirmationContenderRequest $request,
        SampleAnalysis $sampleAnalysis,
        int $contender,
        RemoveConfirmationContender $remove,
        GetConfirmation $get,
    ): JsonResponse {
        $data = $request->validated();
        $analysis = $remove->handle($sampleAnalysis, $data['df'], $data['rep'], $contender);

        return $this->updated($analysis, $get, $data['df'], $data['rep']);
    }

    public function note(
        UpdateConfirmationNoteRequest $request,
        SampleAnalysis $sampleAnalysis,
        UpdateConfirmationNote $update,
        GetConfirmation $get,
    ): JsonResponse {
        $data = $request->validated();
        $analysis = $update->handle($sampleAnalysis, $data['note'] ?? '');

        return $this->updated($analysis, $get, $data['df'], $data['rep']);
    }

    private function updated(
        SampleAnalysis $analysis,
        GetConfirmation $get,
        ?string $df = null,
        int $rep = 0,
    ): JsonResponse {
        $state = $get->handle($analysis, $df, $rep);
        $revision = hash('sha256', json_encode($state, JSON_THROW_ON_ERROR));
        broadcast(new ConfirmationUpdated((int) $analysis->sample, (int) $analysis->id, $revision))->toOthers();

        return (new ConfirmationResource($state))->response();
    }
}
