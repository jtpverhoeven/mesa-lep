<?php

namespace App\Actions\AuditTrail;

use App\AssuranceForms\SampleAssuranceValues;
use App\Models\Assay;
use App\Models\Media;
use App\Models\Result;
use App\Models\Sample;
use App\Models\SampleAnalysis;
use App\Support\DilutionCurve;
use App\Support\UserNames;
use Illuminate\Support\Collection;

/**
 * Everything legacy sampleAuditTrail showed once a sample has been inoculated: who set it up, the dates and
 * assays, the media and material used with their assurance form values, confirmations and results.
 */
class BuildSampleAnalysisAuditTrail
{
    private const ACCUMULATION_MEDIA = ['tht_pfz' => 'PFZ', 'tht_pfz_buizen' => 'PFZ Buizen', 'tht_bpw' => 'BPW'];

    private const DILUTION_COLUMNS = 11;

    public function __construct(
        private UserNames $users,
        private ListAuditRevisions $revisions,
        private BuildConfirmationAuditTrail $confirmations,
    ) {}

    /** @return array<string, mixed> */
    public function handle(Sample $sample, SampleAssuranceValues $assurance): array
    {
        $analyses = $sample->analyses()->with(['assayRecord', 'confirmationRecord', 'results'])->get();
        $media = Media::query()
            ->whereIn('id', $analyses->flatMap(fn (SampleAnalysis $analysis): array => $analysis->assayRecord?->mediaIds() ?? [])->unique())
            ->get()->keyBy('id');

        $audit = [
            'set_up_by' => $this->users->name($assurance->value(0, 'ingezet')),
            'poured_by' => $this->users->name($assurance->value(0, 'gegoten')),
            'accumulation' => $this->accumulation($assurance),
            'dates' => [], 'assays' => [], 'media' => [], 'material' => [], 'confirmations' => [],
            'confirmation_media' => [], 'confirmation_notes' => [], 'results' => [], 'confirmation_results' => [],
            'assay_revisions' => [],
        ];

        foreach ($analyses->values() as $index => $analysis) {
            $volg = $index + 1;
            $assay = $analysis->assayRecord;

            if ($assay === null) {
                continue;
            }

            $custom = json_decode((string) $assay->custom_fields, true);
            $custom = is_array($custom) ? $custom : [];
            $block = $assurance->section(1, (string) $assay->duration);
            $confirmation = $this->confirmations->handle($analysis, $volg, $assurance);

            $audit['dates'][] = [
                'volg' => $volg, 'name' => $assay->name,
                'date_in' => $assurance->formDate()->format('d-m-Y'), 'time_in' => $assurance->value(0, 'instoof'),
                'date_out' => (string) ($block['datum_uitstoof'] ?? ''), 'time_out' => (string) ($block['tijd_uitstoof'] ?? ''),
                'date_read' => (string) ($block['datum_aflezen'] ?? ''), 'time_read' => (string) ($block['tijd_aflezen'] ?? ''),
                'read_by' => $this->users->name($block['afgelezen_door'] ?? null),
            ];
            $audit['assays'][] = [
                'volg' => $volg, 'name' => $assay->name,
                'accredited' => (string) ($custom['accred'] ?? ''), 'technique' => (string) ($custom['techniek'] ?? ''),
                'internal_reference' => (string) ($custom['internrefnummer'] ?? ''), 'conformity' => (string) ($custom['conform'] ?? ''),
                'reference_method' => (string) ($custom['referentiemethode'] ?? ''),
                'assay_id' => $assay->original_id, 'revision' => $this->revisionNumber($assay),
            ];

            foreach ($assay->mediaIds() as $mediaId) {
                $medium = $media->get($mediaId);

                if ((int) $medium?->type === 1) {
                    $audit['media'][] = $this->mediaRow($volg, $medium, $assurance);
                } elseif ((int) $medium?->type === 3) {
                    $audit['material'][] = [
                        'volg' => $volg, 'name' => $medium->name, 'unit' => (string) $medium->supplements,
                        'value' => $assurance->material($mediaId, (string) $medium->acceptable_range),
                    ];
                }
            }

            $audit['confirmations'][] = ['volg' => $volg, 'name' => $assay->name, 'performed' => $confirmation['requested']];
            array_push($audit['confirmation_media'], ...$confirmation['media']);
            array_push($audit['confirmation_results'], ...$confirmation['results']);

            if ($confirmation['note'] !== '') {
                $audit['confirmation_notes'][] = ['volg' => $volg, 'note' => $confirmation['note']];
            }

            $audit['results'][] = $this->results($analysis, $volg);
            $audit['assay_revisions'][] = [
                'volg' => $volg, 'name' => $assay->name,
                'revisions' => $this->revisions->handle('analysis', $analysis->id, $sample->id),
            ];
        }

        return $audit;
    }

    /** @return list<array<string, mixed>> */
    private function accumulation(SampleAssuranceValues $assurance): array
    {
        return collect(self::ACCUMULATION_MEDIA)
            ->map(fn (string $name, string $key): array => ['media' => $name, 'tht' => $assurance->date(2, $key)])
            ->values()
            ->all();
    }

    /** @return array<string, mixed> */
    private function mediaRow(int $volg, Media $medium, SampleAssuranceValues $assurance): array
    {
        $supplements = json_decode((string) $medium->supplements, true);

        return [
            'volg' => $volg, 'name' => $medium->name,
            'tht' => $assurance->date(3, (string) $medium->id, $medium->id),
            'supplements' => collect(is_array($supplements) ? $supplements : [])
                ->filter(fn (mixed $supplement): bool => is_array($supplement))
                ->map(fn (array $supplement): array => [
                    'name' => (string) ($supplement['name'] ?? ''),
                    'tht' => $assurance->date(3, 'extra_'.$medium->id.'_'.($supplement['supplementId'] ?? ''), $medium->id),
                ])
                ->values()
                ->all(),
        ];
    }

    private function revisionNumber(Assay $assay): int
    {
        return Assay::query()->where('original_id', $assay->original_id)->where('id', '<=', $assay->id)->count();
    }

    /**
     * Counts per dilution from 0 to -10, plus the stored end result (legacy msProcess output `kve`).
     *
     * @return array<string, mixed>
     */
    private function results(SampleAnalysis $analysis, int $volg): array
    {
        $byExponent = $analysis->results->groupBy(fn (Result $result): string => (string) DilutionCurve::exponent($result->df));
        $ctValues = [];
        $counts = [];

        for ($exponent = 0; $exponent < self::DILUTION_COLUMNS; $exponent++) {
            /** @var Collection<int, Result>|null $rows */
            $rows = $byExponent->get((string) -$exponent);

            if ($rows === null) {
                $counts[] = 'n.u.';

                continue;
            }

            $kve = [];

            foreach ($rows->sortBy('rep') as $row) {
                $data = is_array($row->data) ? $row->data : [];

                if (array_key_exists('kve', $data)) {
                    $kve[] = (string) $data['kve'];
                }

                if (array_key_exists('Ctwaarde', $data)) {
                    $ctValues[] = (string) $data['Ctwaarde'];
                }
            }

            $counts[] = rtrim(implode(',', $kve), ',');
        }

        return [
            'volg' => $volg,
            'counts' => $counts,
            'end_result' => $analysis->storedResult['output']['kve'] ?? null,
            'ct_value' => rtrim(implode(',', $ctValues), ','),
        ];
    }
}
