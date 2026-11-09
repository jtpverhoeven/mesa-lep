<?php

namespace App\Http\Controllers;

use App\Actions\EmailTemplates\LoadEmailTemplate;
use App\Actions\Reports\CheckTransactionIsMixed;
use App\Actions\Reports\DownloadReportZip;
use App\Actions\Reports\ReportSendRefusedException;
use App\Actions\Reports\ReportTransactionUnavailableException;
use App\Actions\Reports\SendReportTransaction;
use App\Actions\Reports\ShowReportSender;
use App\Actions\SampleFiles\ListSampleFileHistory;
use App\Http\Requests\ReportSenderMixedRequest;
use App\Http\Requests\SendReportRequest;
use App\Models\EmailTemplate;
use App\Models\Export;
use App\Models\SampleFile;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportSenderController extends Controller
{
    public function show(Request $request, int $transaction, ShowReportSender $showReportSender): View
    {
        try {
            $config = $showReportSender->handle($transaction, $request->user());
        } catch (ReportTransactionUnavailableException $exception) {
            abort(404, $exception->getMessage());
        }

        return view('reports.send', ['config' => $config, 'project' => $config['project']]);
    }

    public function download(Export $export): StreamedResponse
    {
        $export->loadMissing(['clientRecord', 'projectRecord']);
        abort_unless($export->pdfExists(), 404, ReportTransactionUnavailableException::missingPdf()->getMessage());

        return Storage::disk('local')->download($export->storagePath(), $export->reportName(true).'.pdf', [
            'Content-Type' => 'application/pdf',
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function zip(int $transaction, DownloadReportZip $downloadReportZip): BinaryFileResponse
    {
        try {
            $archive = $downloadReportZip->handle($transaction);
        } catch (ReportTransactionUnavailableException $exception) {
            abort(404, $exception->getMessage());
        }

        return response()->download($archive['path'], $archive['filename'], ['Cache-Control' => 'private, no-store'])->deleteFileAfterSend();
    }

    public function mixed(ReportSenderMixedRequest $request, int $transaction, CheckTransactionIsMixed $checkTransactionIsMixed): JsonResponse
    {
        try {
            $mixed = $checkTransactionIsMixed->handle($transaction, $request->validated('selected_files') ?? []);
        } catch (ReportTransactionUnavailableException $exception) {
            abort(404, $exception->getMessage());
        }

        return response()->json($mixed);
    }

    public function mail(SendReportRequest $request, int $transaction, SendReportTransaction $sendReportTransaction): JsonResponse
    {
        set_time_limit(300);

        try {
            return response()->json($sendReportTransaction->handle($transaction, $request->mailInput()));
        } catch (ReportSendRefusedException $exception) {
            return response()->json(['status' => false, 'message' => $exception->getMessage()], 422);
        } catch (ModelNotFoundException) {
            return response()->json(['status' => false, 'message' => ReportTransactionUnavailableException::notFound()->getMessage()], 404);
        }
    }

    public function template(EmailTemplate $emailTemplate, LoadEmailTemplate $loadEmailTemplate, string $lang = 'nl'): JsonResponse
    {
        return response()->json($loadEmailTemplate->handle($emailTemplate, $lang));
    }

    public function history(SampleFile $sampleFile, ListSampleFileHistory $listSampleFileHistory): JsonResponse
    {
        return response()->json(['history' => $listSampleFileHistory->handle($sampleFile)]);
    }
}
