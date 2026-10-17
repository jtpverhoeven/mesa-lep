<?php

namespace App\Actions\Printing;

use App\Actions\PrintProfiles\BuildPrintProfilePlan;
use App\Models\Cvar;
use App\Models\LabelDesign;
use App\Models\Printer;
use App\Models\PrintProfile;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class BuildManualLabelPrintPlan
{
    public function __construct(
        private SelectManualPrintSamples $selectSamples,
        private GetLabelPrintContext $sampleContext,
        private GetAnalysisLabelPrintContext $analysisContext,
        private RenderLabelZpl $render,
        private BuildPrintProfilePlan $profiles,
        private GetLabelPrintingSettings $settings,
    ) {}

    /**
     * @param  array<string, mixed>  $options
     * @return array{labels: list<array<string, mixed>>, total_labels: int, sample_count: int}
     */
    public function handle(array $options, string $currentUser): array
    {
        if (($options['mode'] ?? null) === 'legionella') {
            return $this->legionella($options, $currentUser);
        }

        $clock = CarbonImmutable::instance(now());
        $offset = (int) $clock->startOfDay()->diffInDays(CarbonImmutable::createFromFormat('!Y-m-d', $options['date_offset']), false);
        $designs = LabelDesign::query()->orderBy('id')->get()->keyBy('id');
        $printers = Printer::query()->get()->keyBy('id');
        $profiles = PrintProfile::query()->whereKey($options['profile_ids'] ?? [])->orderBy('id')->get();
        $override = ($options['printer'] ?? 0) ?: null;
        $sampleDesign = $this->design($designs, $options['sample_label_select'] ?? 'default', 'default_sample');
        $analysisDesign = $this->design($designs, $options['assay_label_select'] ?? 'default', 'default_analysis');
        $labels = [];
        $sampleCount = 0;
        $bytes = [];
        $alignmentBytes = strlen($this->settings->handle()['alignment']);
        $total = 0;

        $append = function (array $label) use (&$labels, &$bytes, &$total, $alignmentBytes): void {
            $printer = $label['print_to'];
            $bytes[$printer] = ($bytes[$printer] ?? $alignmentBytes) + strlen($label['zpl']) * $label['copies'];
            $total += $label['copies'];

            if ($bytes[$printer] > (int) config('printing.max_spool_bytes', 16 * 1024 * 1024)) {
                throw new InvalidArgumentException('De printopdracht overschrijdt de spoollimiet. Kies een kleiner bereik.');
            }

            $labels[] = $label;
        };

        $samples = $this->selectSamples->handle($options)->with(['client', 'project', 'samplingProcedure', 'analyses.assayRecord', 'analyses.results'])->lazy(100);

        foreach ($samples as $sample) {
            $sampleCount++;
            $context = $this->sampleContext->handle($sample);
            $analyses = $sample->analyses;

            foreach ($analyses as $analysis) {
                $analysis->setRelation('sampleRecord', $sample);
            }

            $selected = $analyses->filter(fn ($analysis): bool => empty($options['assay_type_select']) || (int) $analysis->assayRecord?->type_base === (int) $options['assay_type_select']);

            if ($options['duplicate_label'] ?? false) {
                $parts = explode('.', $options['barcode']);
                $printer = $override ?: $sampleDesign?->default_printer;

                if (count($parts) === 1) {
                    $append($this->label($sampleDesign, $printer, $printers, $context, 1, $clock, $offset, $currentUser));
                } else {
                    $analysis = $analyses->firstWhere('follow_number', (int) $parts[1]);

                    if ($analysis === null) {
                        throw new InvalidArgumentException('Geen analyse gevonden voor deze barcode.');
                    }

                    $analysisContext = $this->analysisContext->handle($analysis, $clock, $offset, $context, isset($parts[2]) ? (int) $parts[2] : null);

                    if ($analysisContext['analysis_labels'] === []) {
                        throw new InvalidArgumentException('Geen verdunning of duplo gevonden voor deze barcode.');
                    }

                    foreach ($analysisContext['analysis_labels'] as $attributes) {
                        $analysisContext['assay'] = array_replace($analysisContext['assay'], $attributes);
                        $append($this->label($analysisDesign, $printer, $printers, $analysisContext, 1, $clock, $offset, $currentUser));
                    }
                }
            }

            if (($options['print_sample'] ?? false) && ($selected->isNotEmpty() || empty($options['assay_type_select']))) {
                $append($this->label($sampleDesign, $override, $printers, $context, (int) $options['print_sample_amount'], $clock, $offset, $currentUser));
            }

            if ($options['print_analysis'] ?? false) {
                foreach ($selected as $analysis) {
                    $analysisContext = $this->analysisContext->handle($analysis, $clock, $offset, $context);

                    foreach ($analysisContext['analysis_labels'] as $attributes) {
                        $analysisContext['assay'] = array_replace($analysisContext['assay'], $attributes);
                        $append($this->label($analysisDesign, $override, $printers, $analysisContext, 1, $clock, $offset, $currentUser));
                    }
                }
            }

            if ($options['also_print'] ?? false) {
                $append($this->label($designs->get((int) $options['also_print_selection']), $override, $printers, $context, (int) $options['also_print_amount'], $clock, $offset, $currentUser));
            }

            foreach ($profiles as $profile) {
                foreach ($this->profiles->handle($profile, $sample, $override, $clock, $offset, $currentUser)['labels'] as $label) {
                    $append($label);
                }
            }

        }

        return ['labels' => $labels, 'total_labels' => $total, 'sample_count' => $sampleCount];
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array{labels: list<array<string, mixed>>, total_labels: int, sample_count: int}
     */
    private function legionella(array $options, string $currentUser): array
    {
        $setting = Cvar::query()->where('cvar', 'GENERIC_LEGIONELLA_LABEL_ID')->first();
        $design = LabelDesign::query()->find((int) ($setting?->value ?? $setting?->default ?? 0));

        if ($design === null || trim($design->zpl) === '') {
            throw new InvalidArgumentException('Stel GENERIC_LEGIONELLA_LABEL_ID in op een bestaand labelontwerp met ZPL.');
        }

        $series = match ($options['leg_matrix']) {
            'A' => [
                ['BCYE +', 'KOUD', 2], ['BCYE +', 'ZUUR', 7], ['BCYE +', 'WARM', 5],
                ['BCYE', 'KOUD', 1], ['BCYE', 'ZUUR', 6], ['BCYE', 'WARM', 4],
                ['BCYE -', 'KOUD', 3],
            ],
            'B' => [['MWY', 'KOUD', 1], ['MWY', 'ZUUR', 3], ['MWY', 'WARM', 2]],
            'C' => [['MWY', 'WARM/ZUUR', 1]],
        };
        $replicates = match ($options['leg_matrix']) {
            'A' => 1,
            'B' => 2,
            'C' => 20,
        };
        $clock = CarbonImmutable::instance(now());
        $labels = [];
        $bytes = strlen($this->settings->handle()['alignment']);

        foreach (range((int) $options['leg_from'], (int) $options['leg_to']) as $number) {
            $shortCode = 'L'.$options['leg_matrix'].$number;

            foreach ($series as [$media, $seriesType, $follow]) {
                for ($replicate = 1; $replicate <= $replicates; $replicate++) {
                    $zpl = $this->render->handle(strtr($design->zpl, [
                        '{barcode}' => $shortCode.'.'.$follow.'.'.$replicate,
                        '{short_code}' => $shortCode.($options['leg_matrix'] === 'C' ? '       '.$replicate : ''),
                        '{media}' => $media,
                        '{series_type}' => $seriesType,
                        '{temperature}' => '',
                    ]), [], $clock, 0, $currentUser);
                    $bytes += strlen($zpl);

                    if ($bytes > (int) config('printing.max_spool_bytes', 16 * 1024 * 1024)) {
                        throw new InvalidArgumentException('De printopdracht overschrijdt de spoollimiet. Kies een kleiner bereik.');
                    }

                    $labels[] = ['event_id' => 0, 'print_label' => $design->id, 'print_to' => (int) $options['leg_printer'], 'copies' => 1, 'zpl' => $zpl];
                }
            }
        }

        return ['labels' => $labels, 'total_labels' => count($labels), 'sample_count' => (int) $options['leg_to'] - (int) $options['leg_from'] + 1];
    }

    private function design(Collection $designs, int|string $selected, string $default): ?LabelDesign
    {
        return $selected === 'default' ? $designs->firstWhere($default, true) : $designs->get((int) $selected);
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array{event_id: int, print_label: int, print_to: int, copies: int, zpl: string}
     */
    private function label(?LabelDesign $design, ?int $override, Collection $printers, array $context, int $copies, CarbonImmutable $clock, int $offset, string $user): array
    {
        $printer = $override ?: $design?->default_printer;

        if ($design === null || trim($design->zpl) === '' || ! $printers->has($printer)) {
            throw new InvalidArgumentException('Het geselecteerde labelontwerp of de standaardprinter ontbreekt, of het ZPL is leeg.');
        }

        return ['event_id' => 0, 'print_label' => $design->id, 'print_to' => $printer, 'copies' => $copies,
            'zpl' => $this->render->handle($design->zpl, $context, $clock, $offset, $user)];
    }
}
