<?php

namespace App\Actions\Billing;

use App\Models\Export;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Color;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Writer;

/**
 * Writes a billing overview as a single XLSX sheet with the sections stacked as on the page.
 */
class ExportBillingOverview
{
    /**
     * @param  array<string, mixed>  $overview  Result of CreateBillingOverview::handle().
     */
    public function write(array $overview, string $path): void
    {
        $title = (new Style)->withFontBold(true)->withFontSize(12);
        $heading = (new Style)->withFontBold(true);
        $wrap = (new Style)->withShouldWrapText(true);
        $surcharge = (new Style)->withBackgroundColor(Color::YELLOW)->withFontColor(Color::BLACK);
        $client = $overview['client'] ?? ['name' => null, 'debit_number' => null, 'categories' => []];

        $writer = new Writer;
        $writer->openToFile($path);
        $writer->getCurrentSheet()->setName('Facturatie');

        $writer->addRow(Row::fromValuesWithStyle(['Project gegevens voor facturatie'], $title));
        $writer->addRow(Row::fromValuesWithStyle(['Klant'], $heading));
        $writer->addRow(Row::fromValues([
            (string) $client['name'],
            'Debiteur nummer: '.$client['debit_number'],
            'Klant categorie: '.implode(',', $client['categories']),
        ]));
        $writer->addRow(Row::fromValues([]));

        $writer->addRow(Row::fromValuesWithStyle(['Ingescande projecten'], $heading));
        $writer->addRow(Row::fromValuesWithStyle(
            ['Status', 'Referentie MAZ', 'Revisie', 'Referentie klant', 'Aantal monsters', 'Bemonsterdatum', 'Ontvangstdatum', 'Inzetdatum'],
            $heading,
        ));
        foreach ($overview['project_rows'] as $project) {
            $writer->addRow(new Row([
                Cell::fromValue($project['status']),
                Cell::fromValue('MAZ-L '.$project['reference']),
                Cell::fromValue($project['revision']),
                Cell::fromValue($project['project_name']),
                Cell::fromValue($project['samples_count']),
                Cell::fromValue($project['sampling_date']),
                Cell::fromValue($project['received_date']),
                Cell::fromValue($project['inoculation_date'], $wrap),
            ]));
        }
        $writer->addRow(Row::fromValues([]));

        $writer->addRow(Row::fromValuesWithStyle(['Monsters & toeslagen'], $heading));
        $writer->addRow(Row::fromValuesWithStyle(['', '', 'Toeslagen'], $heading));
        $writer->addRow(Row::fromValuesWithStyle(['Type monster', 'Totaal aantal', 'waarvan met 50%', 'waarvan met 100%'], $heading));
        foreach ($overview['sample_types'] as $type) {
            $writer->addRow(new Row([
                Cell::fromValue($type['header']),
                Cell::fromValue($type['count']),
                Cell::fromValue($type['count50'], $type['count50'] > 0 ? $surcharge : null),
                Cell::fromValue($type['count100'], $type['count100'] > 0 ? $surcharge : null),
            ]));
        }
        $writer->addRow(Row::fromValues([]));

        $writer->addRow(Row::fromValuesWithStyle(['Analyses'], $heading));
        if ($overview['articles'] === []) {
            $writer->addRow(Row::fromValues(['Geen resultaten gevonden voor dit project']));
        } else {
            $writer->addRow(Row::fromValuesWithStyle(['Analyses', 'Artikel code', 'Aantal', 'Aantal met bevestiging', 'Waarvan positief'], $heading));
            foreach ($overview['articles'] as $article) {
                $writer->addRow(Row::fromValues([
                    $article['name'],
                    $article['article_code'],
                    $article['total'],
                    $article['conf_requested_count'],
                    $article['positives'],
                ]));
            }
        }

        $writer->close();
    }

    /**
     * @param  array<string, mixed>  $overview
     */
    public function filename(array $overview): string
    {
        return Export::safeFilename(trim('Facturatie '.($overview['client']['name'] ?? '').' '.now('Europe/Amsterdam')->format('d-m-Y'))).'.xlsx';
    }
}
