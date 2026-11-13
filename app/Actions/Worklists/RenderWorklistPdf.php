<?php

namespace App\Actions\Worklists;

use App\Actions\Media\StoreEditorImages;
use App\Models\Worklist;
use Illuminate\Support\Facades\Storage;
use Mpdf\HTMLParserMode;
use Mpdf\Mpdf;
use Mpdf\Output\Destination;

class RenderWorklistPdf
{
    public function __construct(private PaginateWorklistRows $paginate) {}

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    public function handle(Worklist $worklist, array $rows, string $date): string
    {
        $pdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'orientation' => strtoupper((string) $worklist->orientation) === 'P' ? 'P' : 'L',
            'default_font_size' => 7,
            'default_font' => 'arial',
            'margin_left' => 10,
            'margin_right' => 10,
            'margin_top' => 30,
            'margin_bottom' => 13,
            'margin_header' => 5,
            'margin_footer' => 5,
            'tempDir' => storage_path('framework/cache/mpdf'),
            'whitelistStreamWrappers' => ['file'],
        ]);
        $data = ['worklist' => $worklist, 'date' => $date];

        $pdf->SetHTMLHeader(view('worklists.pdf.header', $data)->render());
        $pdf->SetHTMLFooter(view('worklists.pdf.footer', $data)->render());
        $pdf->WriteHTML(file_get_contents(resource_path('views/worklists/pdf/style.css')), HTMLParserMode::HEADER_CSS);
        $pdf->WriteHTML(view('worklists.pdf.document', [
            'worklist' => $worklist,
            'columns' => $worklist->columnList(),
            'groups' => array_values($worklist->colum_groups ?? []),
            'pages' => $this->paginate->handle($worklist, $rows),
            'extraPages' => $this->embedImages($worklist, (string) $worklist->extra_pages),
        ])->render(), HTMLParserMode::HTML_BODY);

        return $pdf->Output('', Destination::STRING_RETURN);
    }

    /** mPDF cannot request the authenticated image route, so stored images are inlined. */
    private function embedImages(Worklist $worklist, string $html): string
    {
        $directory = StoreEditorImages::directory($worklist);

        return preg_replace_callback(
            '#(<img\b[^>]*?\bsrc=")[^"]*/worklists/'.$worklist->id.'/images/([a-f0-9]{40}\.(png|jpg|gif|webp))(")#i',
            function (array $match) use ($directory): string {
                $path = $directory.'/'.$match[2];

                if (! Storage::disk('local')->exists($path)) {
                    return $match[0];
                }

                $mime = $match[3] === 'jpg' ? 'jpeg' : strtolower($match[3]);

                return $match[1].'data:image/'.$mime.';base64,'.base64_encode(Storage::disk('local')->get($path)).$match[4];
            },
            $html,
        ) ?? $html;
    }
}
