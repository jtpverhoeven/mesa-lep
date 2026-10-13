<?php

namespace App\Actions\PrintProfiles;

use App\Actions\Printing\GetAnalysisLabelPrintContext;
use App\Actions\Printing\GetLabelPrintContext;
use App\Actions\Printing\GetLabelPrintingSettings;
use App\Actions\Printing\RenderLabelZpl;
use App\Models\LabelDesign;
use App\Models\Printer;
use App\Models\PrintProfile;
use App\Models\Sample;
use App\Models\SampleAnalysis;
use DateTimeInterface;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class BuildPrintProfilePlan
{
    public const MAX_STEPS = 10000;

    public const MAX_LABELS = 10000;

    public function __construct(
        private ParsePrintProfile $parsePrintProfile,
        private GetLabelPrintContext $getLabelPrintContext,
        private GetAnalysisLabelPrintContext $getAnalysisLabelPrintContext,
        private RenderLabelZpl $renderLabelZpl,
        private GetLabelPrintingSettings $settings,
    ) {}

    /**
     * Profiles use event_id 0 because they do not originate from a label event.
     *
     * @return array{labels: list<array{event_id: int, profile_id: int|null, line: int, sample_analysis_id: int|null, print_label: int, label_name: string, print_to: int, printer_name: string, copies: int, zpl: string}>, trace: list<array{line: int, command: string, message: string}>, total_labels: int}
     */
    public function handle(PrintProfile $profile, Sample $sample, ?int $printerOverride = null, ?DateTimeInterface $printedAt = null, int $dateOffset = 0, string $currentUser = ''): array
    {
        if ($printerOverride !== null && $printerOverride < 0) {
            throw new InvalidArgumentException('Ongeldige printeroverride.');
        }

        $instructions = $this->parsePrintProfile->handle($profile->profile);
        $lines = array_keys($instructions);
        $positions = array_flip($lines);
        $designs = LabelDesign::query()->whereKey(array_column($instructions, 'label'))->get()->keyBy('id');
        $printers = Printer::query()->whereKey($printerOverride ? [$printerOverride] : array_column($instructions, 'printer'))->get()->keyBy('id');
        $analyses = $sample->analyses()->with(['assayRecord', 'results'])->get();

        foreach ($analyses as $analysis) {
            $analysis->setRelation('sampleRecord', $sample);
        }

        $sampleContext = $this->getLabelPrintContext->handle($sample);
        $clock = $printedAt ?? now();
        $labels = [];
        $trace = [];
        $totalLabels = 0;
        $spoolBytes = [];
        $prefixBytes = strlen($this->settings->handle()['alignment']);
        $maxBytes = (int) config('printing.max_spool_bytes', 16 * 1024 * 1024);
        $position = 0;
        $steps = 0;

        while (isset($lines[$position])) {
            $line = $lines[$position];
            $instruction = $instructions[$line];

            if (++$steps > self::MAX_STEPS) {
                throw new InvalidArgumentException('Regel '.$line.': uitvoeringslimiet bereikt; controleer op een oneindige lus.');
            }

            $trace[] = ['line' => $line, 'command' => trim($instruction['command'].' '.($instruction['mode'] ?? '')), 'message' => $instruction['message'] ?? ''];

            if ($instruction['command'] === 'EXIT') {
                break;
            }

            if ($instruction['command'] === 'GOTO') {
                $position = $positions[$instruction['goto']];

                continue;
            }

            if ($instruction['command'] === 'SAMPLE' && $instruction['mode'] !== 'PRINT') {
                $matches = match ($instruction['mode']) {
                    'HASONLYONE' => $analyses->count() <= 1,
                    'HASALL' => $this->selectAnalyses($analyses, 'ONLY', $instruction['ids'])->count() >= count($instruction['ids']),
                    'HASANYWITHCONFIRMATION' => $this->selectAnalyses($analyses, 'ONLYWITHCONFIRMATION', $instruction['ids'])->isNotEmpty(),
                    default => $this->selectAnalyses($analyses, 'ONLY', $instruction['ids'])->isNotEmpty(),
                };
                $target = $matches ? $instruction['goto'] : $instruction['else'];
                $trace[array_key_last($trace)]['message'] = $target === null ? 'Volgende regel' : 'GOTO '.$target;
                $position = $target === null ? $position + 1 : $positions[$target];

                continue;
            }

            if (isset($instruction['label'])) {
                $design = $designs->get($instruction['label']);
                $printer = $printers->get($printerOverride ?: $instruction['printer']);

                if ($design === null || trim($design->zpl) === '' || $printer === null) {
                    throw new InvalidArgumentException('Regel '.$line.': labelontwerp of printer ontbreekt, of het ZPL is leeg.');
                }

                $contexts = [];

                if ($instruction['command'] === 'SAMPLE') {
                    $contexts[] = [null, $sampleContext];
                } else {
                    $selected = $this->selectAnalyses($analyses, $instruction['mode'], $instruction['ids'] ?? []);
                    $restrict = $instruction['single'] ? (in_array($instruction['mode'], ['NOT', 'NOTGROUP'], true) ? true : 1) : null;

                    foreach ($selected as $analysis) {
                        $context = $this->getAnalysisLabelPrintContext->handle($analysis, $clock, $dateOffset, $sampleContext, $restrict);

                        foreach ($context['analysis_labels'] as $attributes) {
                            $labelContext = $context;
                            $labelContext['assay'] = array_replace($context['assay'], $attributes);
                            $contexts[] = [$analysis->id, $labelContext];

                            if (count($contexts) * $instruction['copies'] > self::MAX_LABELS - $totalLabels) {
                                throw new InvalidArgumentException('Regel '.$line.': te veel labels (maximaal '.self::MAX_LABELS.').');
                            }
                        }
                    }
                }

                foreach ($contexts as [$analysisId, $context]) {
                    $zpl = $this->renderLabelZpl->handle($design->zpl, $context, $clock, $dateOffset, $currentUser);
                    $totalLabels += $instruction['copies'];
                    $spoolBytes[$printer->id] = ($spoolBytes[$printer->id] ?? $prefixBytes) + strlen($zpl) * $instruction['copies'];

                    if ($totalLabels > self::MAX_LABELS || $spoolBytes[$printer->id] > $maxBytes) {
                        throw new InvalidArgumentException('Regel '.$line.': label- of spoollimiet overschreden.');
                    }

                    $labels[] = [
                        'event_id' => 0, 'profile_id' => $profile->id, 'line' => $line, 'sample_analysis_id' => $analysisId,
                        'print_label' => $design->id, 'label_name' => $design->name,
                        'print_to' => $printer->id, 'printer_name' => $printer->name,
                        'copies' => $instruction['copies'], 'zpl' => $zpl,
                    ];
                }
            }

            $position++;
        }

        return ['labels' => $labels, 'trace' => $trace, 'total_labels' => $totalLabels];
    }

    /**
     * @param  Collection<int, SampleAnalysis>  $analyses
     * @param  list<int>  $ids
     * @return Collection<int, SampleAnalysis>
     */
    private function selectAnalyses(Collection $analyses, string $mode, array $ids): Collection
    {
        return $analyses->filter(function (SampleAnalysis $analysis) use ($mode, $ids): bool {
            $baseId = (int) ($analysis->original_assay_base ?? $analysis->assay_base);
            $matches = in_array($baseId, $ids, true);

            return match ($mode) {
                'ANY' => true,
                'GROUP' => in_array((int) $analysis->assayRecord?->type_base, $ids, true),
                'NOT', 'NOTGROUP' => ! $matches,
                'ONLYWITHCONFIRMATION' => $matches && (int) $analysis->conf_requested === 1,
                default => $matches,
            };
        });
    }
}
