<?php

namespace App\Http\Controllers;

use App\Actions\SampleFiles\NormalizeSamplePhoto;
use App\Actions\Samples\FindScannedSample;
use App\Actions\Samples\LookupSample;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\View\View;

class SamplePhotoController extends Controller
{
    public function index(NormalizeSamplePhoto $normalizePhoto): View
    {
        return view('samples.photos', ['maxEdge' => $normalizePhoto->maxEdge()]);
    }

    public function show(Request $request, FindScannedSample $findSample, LookupSample $lookup): JsonResponse
    {
        $data = $request->validate(['barcode' => ['required', 'string', 'max:32']]);
        $sample = $findSample->handle(trim($data['barcode']));
        abort_if($sample === null, 404);

        return response()->json(['data' => Arr::only($lookup->handle($sample->barcode), [
            'sample', 'sample_fields', 'sample_extra', 'client', 'registered_by', 'sampling_method',
            'project_follow_number', 'project_info', 'project_url', 'project_samples',
        ])]);
    }
}
