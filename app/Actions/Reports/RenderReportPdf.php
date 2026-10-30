<?php

namespace App\Actions\Reports;

use App\Support\AccountImages;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Mpdf\HTMLParserMode;
use Mpdf\Mpdf;
use Mpdf\Output\Destination;

class RenderReportPdf
{
    /**
     * @param  list<array<string, mixed>>  $reports
     */
    public function handle(array $reports, bool $preview, bool $temporary = false): string
    {
        $pdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'default_font_size' => 7,
            'default_font' => 'arial',
            'margin_left' => 10,
            'margin_right' => 10,
            'margin_top' => 35,
            'margin_bottom' => 50,
            'margin_header' => 5,
            'margin_footer' => 5,
            'tempDir' => storage_path('framework/cache/mpdf'),
            'whitelistStreamWrappers' => ['file'],
        ]);
        $pdf->SetProtection(['copy', 'print']);
        $pdf->AliasNbPages('[pagetotal]');
        $project = $reports[0]['project'];
        $authorized = (int) $project->auth_status === 1;
        $watermark = $this->watermark($authorized, $preview, $temporary);
        $signature = null;

        if ($authorized) {
            $user = $project->authorizedBy;
            $path = $user === null ? null : AccountImages::signaturePath($user, 'nl');

            if ($path === null || ! Storage::disk('local')->exists($path)) {
                throw ValidationException::withMessages(['report' => 'De Nederlandse handtekening van de autorisator ontbreekt. Importeer de handtekening of voeg deze toe via de accountinstellingen.']);
            }

            $signature = Storage::disk('local')->path($path);
        }

        $pdf->SetWatermarkText($watermark);
        $pdf->showWatermarkText = $watermark !== '';
        $pdf->WriteHTML(file_get_contents(resource_path('views/reports/pdf/maz2020/style.css')), HTMLParserMode::HEADER_CSS);
        // mPDF 8 requires all edges to be explicit for collapsed rows with a left border.
        $pdf->WriteHTML('table.innerAdTable tr { border-top: 0px solid #000000; border-right: 0px solid #000000; border-bottom: 0px solid #000000; }', HTMLParserMode::HEADER_CSS);
        $html = '';

        foreach ($reports as $index => $report) {
            if ($index > 0) {
                $html .= '<pagebreak /><div style="text-align: center; padding-top: 250px;"><h1>Einde rapport</h1></div><pagebreak />';
            }

            $html .= view('reports.pdf.maz2020.report', [
                ...$report,
                'signature' => $signature,
                'assets' => resource_path('views/reports/pdf/maz2020/assets'),
                'includeHeaders' => $index === 0,
            ])->render();
        }

        $pdf->WriteHTML($html, HTMLParserMode::HTML_BODY);

        return $pdf->Output('', Destination::STRING_RETURN);
    }

    public function watermark(bool $authorized, bool $preview, bool $temporary): string
    {
        return match (true) {
            $preview && $temporary => $authorized ? 'TIJDELIJK RAPPORT VOORBEELD - NIET VOOR DISTRIBUTIE' : 'VOORLOPIG RAPPORT VOORBEELD - NIET GEAUTHORISEERD',
            $preview => $authorized ? 'VOORBEELD - NIET VOOR DISTRIBUTIE' : 'VOORBEELD - NIET GEAUTHORISEERD',
            default => $authorized ? '' : 'NIET GEAUTHORISEERD',
        };
    }
}
