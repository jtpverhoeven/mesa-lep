<?php

namespace App\Actions\Reports;

use App\Actions\Results\CalculateAnalysisResult;
use App\Models\Assay;
use App\Models\Footer;
use App\Models\Project;
use App\Models\Sample;
use App\Models\SampleAnalysis;
use App\Support\ReportHtml;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\ValidationException;
use UnexpectedValueException;

class BuildProjectReport
{
    public function __construct(private CalculateAnalysisResult $calculateResult) {}

    /**
     * @param  Collection<int, Sample>  $samples
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>|null
     */
    public function handle(Project $project, Collection $samples, array $options, int $printVersion): ?array
    {
        $project->loadMissing(['client', 'authorizedBy', 'samples']);

        if ($project->getRelation('client') === null) {
            throw ValidationException::withMessages(['report' => 'De opdrachtgever van dit project ontbreekt.']);
        }
        $samples->loadMissing([
            'metadata', 'samplingProcedure', 'analyses.assayRecord',
            'analyses.assayProfile.referenceSource', 'analyses.roamingAnalysis.referenceSource',
            'analyses.roamingSettings.referenceSource',
        ]);
        $methods = [];
        $sampling = [];
        $rows = [];
        $order = array_flip(array_map('intval', $options['assay_order']));
        $included = array_map('intval', $options['assay_ids']);
        $follow = 1;

        foreach ($samples as $sample) {
            $results = [];

            foreach ($sample->analyses as $analysis) {
                $assay = $analysis->assayRecord;

                if ($assay === null) {
                    throw new UnexpectedValueException("Analysis [{$analysis->id}] has no assay.");
                }

                if (! in_array((int) $assay->id, $included, true) || (int) $assay->hide_report === 1) {
                    continue;
                }

                $methods[$assay->id] = [
                    'assay' => $assay,
                    'fields' => $this->fields($assay->custom_fields),
                    'position' => $order[$assay->id] ?? 0,
                ];
                $results[$assay->id] = $this->result($analysis, $assay, (bool) $options['showViolation']);
            }

            if ($results === []) {
                continue;
            }

            if ($sample->samplingProcedure !== null) {
                $procedure = $sample->samplingProcedure;
                $sampling[$procedure->id] = ['procedure' => $procedure, 'fields' => $this->fields($procedure->fields)];
            }

            $rows[] = [
                'sample' => $sample,
                'follow' => $follow++,
                'description' => $this->sampleDescription($sample),
                'results' => $results,
            ];
        }

        if ($rows === []) {
            return null;
        }

        uasort($methods, fn (array $left, array $right): int => $left['position'] <=> $right['position']);
        $number = 1;

        foreach ($sampling as &$method) {
            $method['number'] = (int) $method['procedure']->hide === 1 ? null : $number++;
        }
        unset($method);

        foreach ($methods as &$method) {
            $method['number'] = $number++;
        }
        unset($method);

        // Legacy accreditation is report-wide and is based on assays, not sampling procedures.
        $accredited = collect($methods)->contains(fn (array $method): bool => strtoupper(trim($method['fields']['accred'] ?? '')) === 'Q');
        $footer = Footer::query()->where('name', $options['templateSelect'])->where('lang', 'nl')
            ->where('q', (int) $accredited)->orderBy('id')->first();

        if ($footer === null || trim($footer->data) === '') {
            throw ValidationException::withMessages([
                'report' => 'Geen voettekst gevonden voor '.$options['templateSelect'].', nl, RvA-scope '.(int) $accredited.'. Configureer de goedgekeurde certificaattekst in Beheer.',
            ]);
        }

        return [
            'project' => $project,
            'client' => $project->getRelation('client'),
            'info' => $this->reportInfo($project, $printVersion),
            'samples' => $rows,
            'sampling' => $sampling,
            'methods' => $methods,
            'notes' => $this->notes($project, $samples),
            'footer' => ReportHtml::richText($footer->data),
            'accredited' => $accredited,
            'print_version' => $printVersion,
        ];
    }

    /** @return array<string, mixed> */
    private function result(SampleAnalysis $analysis, Assay $assay, bool $highlight): array
    {
        $calculation = $this->calculateResult->handle($analysis);
        $key = $calculation['reportIn'] ?? null;

        if (! is_string($key) || ! array_key_exists($key, $calculation['output'] ?? [])) {
            throw new UnexpectedValueException("Analysis [{$analysis->id}] has no reportable output.");
        }

        $settings = $analysis->calculationSettings();

        if ($settings === null) {
            throw new UnexpectedValueException("Analysis [{$analysis->id}] has no reporting settings.");
        }

        $output = (string) $calculation['output'][$key];
        $reference = $settings->reference['ref_'.$key] ?? null;
        $referenceText = '';

        if ($reference !== null && ! in_array((string) $reference, ['', '+', '-'], true)) {
            $referenceText = (string) $reference;

            if (! str_contains($referenceText, '.') && ! str_contains($referenceText, ',') && is_numeric($referenceText)) {
                $referenceText = number_format((float) $referenceText, 0, ',', '.');
            }
        }

        return [
            'output' => ReportHtml::richText($output),
            'reference' => $referenceText,
            'source' => $settings->referenceSource?->localizedName('nl') ?? '',
            'indicator' => $this->indicator($output, $referenceText, $calculation['disposition'][$key] ?? null, $assay, $highlight),
        ];
    }

    private function indicator(string $output, string $reference, mixed $disposition, Assay $assay, bool $highlight): string
    {
        if (! $highlight || ! filter_var($assay->uses_indicator, FILTER_VALIDATE_BOOLEAN)) {
            return 'white';
        }

        $value = str_replace(',', '.', preg_replace('/[^0-9,]/', '', $output));
        $hasReference = trim(html_entity_decode($reference, ENT_QUOTES, 'UTF-8')) !== '';
        $trip = filter_var($assay->uses_trip_indicator, FILTER_VALIDATE_BOOLEAN);

        if ($disposition !== null && ! is_numeric($value)) {
            if ($disposition === '+') {
                return $trip ? '#ff0000' : 'white';
            }

            return $disposition === '.' || ! $hasReference ? 'white' : '#7bc144';
        }

        if (! is_numeric($value)) {
            return 'white';
        }

        $numeric = trim($output) === '<1' ? 0.0 : (float) $value;
        $limit = preg_replace('/[^0-9]/', '', $reference);
        $comparable = str_replace(['<sup>', '</sup>'], '', $output);

        if ((str_contains($comparable, '<') && $numeric > (float) $limit)
            || (str_contains($comparable, '>') && $numeric < (float) $limit)) {
            return 'white';
        }

        if ($limit !== '' && $numeric > (float) $limit) {
            return $trip ? '#ff0000' : 'white';
        }

        return $limit !== '' ? '#7bc144' : 'white';
    }

    private function sampleDescription(Sample $sample): HtmlString
    {
        $custom = $this->fields($sample->custom_fields);
        $extra = $this->fields($sample->sample_extra);
        $details = '';
        $portal = in_array((int) $sample->source, [1, 3], true);
        $description = trim($sample->client_description ?? '');

        if (trim($custom['details'] ?? '') !== '') {
            $details = ($portal ? 'Aanvullende informatie: ' : '').ReportHtml::lines($custom['details']);
        }

        $hasDetails = trim($details) !== '';

        if (! empty($extra['location'])) {
            $details .= '<br />Ruimte: '.e($extra['location']);
        }

        if ($sample->metadata->isNotEmpty()) {
            $details .= $hasDetails ? '<br />' : '';

            foreach ($sample->metadata as $metadata) {
                $details .= e(trim($metadata->name)).': '.ReportHtml::lines($metadata->value).'<br />';
            }
        }

        if (($portal || mb_strtolower($description) !== 'geen omschrijving beschikbaar') && $description !== '') {
            $details = 'Omschrijving: '.e($description).'<br />'.$details;
        }

        return new HtmlString($details);
    }

    /** @return array<string, mixed> */
    private function reportInfo(Project $project, int $printVersion): array
    {
        $firstSample = $project->samples
            ->filter(fn (Sample $sample): bool => $sample->sample_innoculated !== '' && $sample->sample_innoculated !== null)
            ->sortBy('sample_innoculated', SORT_STRING)->first();
        $inoculatedAt = $firstSample?->sample_innoculated;
        $info = [
            'project_generated' => now('Europe/Amsterdam')->format('d-m-Y'),
            'project_reference' => $project->reference.'.'.$project->revision.'.'.$printVersion,
            'project_inzet' => $inoculatedAt ? Carbon::createFromTimestamp((int) $inoculatedAt, 'Europe/Amsterdam')->format('d-m-Y') : 'Onbekend',
            'shown_client_reference' => trim($project->project_name ?? '') !== trim($project->reference ?? '') ? (string) $project->project_name : '',
        ];

        return [...$info, ...$this->fields($project->custom_fields), ...$this->fields($project->project_extra), 'project_reference' => $info['project_reference']];
    }

    /** @param Collection<int, Sample> $samples
     * @return list<array{barcode: string, text: HtmlString}>
     */
    private function notes(Project $project, Collection $samples): array
    {
        $notes = $this->fields($project->project_notes);
        ksort($notes);
        $result = [];

        foreach ($notes as $id => $text) {
            if (empty($text) || ($id !== 'project' && ! $samples->contains('id', $id))) {
                continue;
            }

            $note = ['barcode' => $id === 'project' ? 'Algemeen' : 'MAZ-L '.$samples->firstWhere('id', $id)->barcode, 'text' => ReportHtml::lines($text)];

            if ($id === 'project') {
                array_unshift($result, $note);
            } else {
                $result[] = $note;
            }
        }

        return $result;
    }

    /** @return array<string|int, mixed> */
    private function fields(?string $json): array
    {
        $fields = json_decode($json ?: '{}', true, flags: JSON_THROW_ON_ERROR);

        if ($fields === null) {
            return [];
        }

        if (! is_array($fields)) {
            throw new UnexpectedValueException('Report fields must contain a JSON object.');
        }

        return $fields;
    }
}
