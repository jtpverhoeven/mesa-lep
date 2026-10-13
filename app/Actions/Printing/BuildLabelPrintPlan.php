<?php

namespace App\Actions\Printing;

use App\Actions\LabelEvents\EvaluateLabelEvent;
use App\Models\LabelEvent;
use App\Models\Printer;
use App\Models\Sample;
use App\Models\SampleAnalysis;
use App\Models\SampleBuffer;
use DateTimeInterface;
use InvalidArgumentException;
use LogicException;

class BuildLabelPrintPlan
{
    public function __construct(private EvaluateLabelEvent $evaluateLabelEvent, private RenderLabelZpl $renderLabelZpl, private GetLabelPrintContext $getLabelPrintContext, private GetAnalysisLabelPrintContext $getAnalysisLabelPrintContext) {}

    /**
     * @param  array{sample?: array<string, mixed>, analysis?: array<string, mixed>, assay?: array<string, mixed>, client?: array<string, mixed>, method?: array<string, mixed>, project?: array<string, mixed>, sample_field_types?: array<string, string>, analysis_labels?: list<array<string, mixed>>}  $context
     * @return list<array{event_id: int, print_label: int, print_to: int, copies: int, zpl: string}>
     */
    public function handle(int $group, array $context, ?int $printerOverride = null, ?DateTimeInterface $printedAt = null, int $dateOffset = 0, string $currentUser = ''): array
    {
        if (! array_key_exists($group, LabelEvent::GROUPS) || ($printerOverride !== null && $printerOverride < 0)) {
            throw new InvalidArgumentException('Invalid label-event group or printer override.');
        }

        $override = $printerOverride ? Printer::query()->findOrFail($printerOverride) : null;
        $events = LabelEvent::query()->with(['labelDesign:id,zpl', 'printer:id'])
            ->where('event_group', $group)->orderBy('id')->get();
        $clock = $printedAt ?? now();
        $plan = [];

        foreach ($events as $event) {
            if (! $this->evaluateLabelEvent->handle($event->getAttributes(), $context['sample'] ?? [], $context['analysis'] ?? [], $context['assay'] ?? [])) {
                continue;
            }

            if ($event->labelDesign === null || ($override === null && $event->printer === null)) {
                throw new LogicException('Label event #'.$event->id.' references a missing label design or printer.');
            }

            if ($event->copies < 1 || $event->copies > LabelEvent::MAX_COPIES || trim($event->labelDesign->zpl) === '') {
                throw new LogicException('Label event #'.$event->id.' has an invalid copy count or empty ZPL.');
            }

            foreach ($context['analysis_labels'] ?? [[]] as $labelAttributes) {
                $labelContext = $context;
                $labelContext['assay'] = array_replace($context['assay'] ?? [], $labelAttributes);
                $plan[] = [
                    'event_id' => $event->id,
                    'print_label' => $event->print_label,
                    'print_to' => $override?->id ?? $event->print_to,
                    'copies' => $event->copies,
                    'zpl' => $this->renderLabelZpl->handle($event->labelDesign->zpl, $labelContext, $clock, $dateOffset, $currentUser),
                ];
            }
        }

        return $plan;
    }

    /** @return list<array{event_id: int, print_label: int, print_to: int, copies: int, zpl: string}> */
    public function forSampleRegistration(Sample $sample, ?int $printerOverride = null, ?DateTimeInterface $printedAt = null, int $dateOffset = 0, string $currentUser = ''): array
    {
        return $this->handle(1, $this->getLabelPrintContext->handle($sample), $printerOverride, $printedAt, $dateOffset, $currentUser);
    }

    /** @return list<array{event_id: int, print_label: int, print_to: int, copies: int, zpl: string}> */
    public function forThtApproval(SampleBuffer $sample, ?int $printerOverride = null, ?DateTimeInterface $printedAt = null, int $dateOffset = 0, string $currentUser = ''): array
    {
        return $this->handle(3, $this->getLabelPrintContext->handle($sample), $printerOverride, $printedAt, $dateOffset, $currentUser);
    }

    /** @return list<array{event_id: int, print_label: int, print_to: int, copies: int, zpl: string}> */
    public function forAnalysisAddition(SampleAnalysis $analysis, ?int $printerOverride = null, ?DateTimeInterface $printedAt = null, int $dateOffset = 0, string $currentUser = ''): array
    {
        $clock = $printedAt ?? now();
        $context = $this->getAnalysisLabelPrintContext->handle($analysis, $clock, $dateOffset);

        return $this->handle(2, $context, $printerOverride, $clock, $dateOffset, $currentUser);
    }
}
