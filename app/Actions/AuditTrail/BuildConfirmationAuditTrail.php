<?php

namespace App\Actions\AuditTrail;

use App\AssuranceForms\SampleAssuranceValues;
use App\Confirmations\AssayConfirmationConfiguration;
use App\Models\ConfKeyStore;
use App\Models\Media;
use App\Models\SampleAnalysis;
use App\Support\UserNames;

/**
 * The confirmation part of the sample audit trail (legacy sampleAuditTrail): which confirmation media were
 * used for every dilution and replicate, who set them up and read them, and what the racetrack showed.
 */
class BuildConfirmationAuditTrail
{
    private const NOT_APPLICABLE = 'NVT';

    private const RACETRACK_COLUMNS = 5;

    public function __construct(private UserNames $users) {}

    /**
     * @return array{requested: bool, note: string, media: list<array<string, mixed>>, results: list<array<string, mixed>>}
     */
    public function handle(SampleAnalysis $analysis, int $volg, SampleAssuranceValues $assurance): array
    {
        $assay = $analysis->assayRecord;
        $confirmation = $analysis->confirmationRecord;
        $audit = ['requested' => false, 'note' => trim((string) $confirmation?->note), 'media' => [], 'results' => []];

        if ($assay === null || (int) $assay->confirmation === 0 || (int) $analysis->conf_requested !== 1) {
            return $audit;
        }

        $audit['requested'] = true;
        $steps = AssayConfirmationConfiguration::decode($assay->confirmation_script);
        $support = AssayConfirmationConfiguration::decode($assay->confirmation_support);
        $global = (int) $assay->confirmation_type === 0;
        $inUse = $confirmation?->in_use ?? [];
        $racetrack = $confirmation?->racetrack ?? [];
        $metadata = $confirmation?->metadata ?? [];
        $mediaIds = collect([...$steps, ...$support])->pluck('mediaId')->map(fn (mixed $id): int => (int) $id)->filter()->unique();
        $names = Media::query()->whereIn('id', $mediaIds)->pluck('name', 'id');
        $controls = ConfKeyStore::query()->whereIn('media', $mediaIds)->get()
            ->mapWithKeys(fn (ConfKeyStore $row): array => [$row->innocdate.'|'.$row->media.'|'.$row->param => $row->value]);

        foreach ($analysis->results as $result) {
            $df = $global ? 'global' : (string) $result->df;
            $rep = (int) $result->rep;
            $contenders = $racetrack[$df][$rep] ?? [];

            if (empty($contenders)) {
                continue;
            }

            $dilution = $global ? 'Globaal' : $df;

            foreach ($steps as $index => $step) {
                $mediaId = (int) $step['mediaId'];

                if (! $this->isInUse($inUse[$df][$rep][$mediaId] ?? false)) {
                    continue;
                }

                $chain = (int) ($step['chainId'] ?? $index + 1) - 1;
                $stepMetadata = $metadata[$df][$rep][$chain] ?? [];
                $setUpOn = $stepMetadata[$mediaId.'_inzet'] ?? null;
                $tested = count($contenders);
                $ratio = $metadata[$df][$rep]['ratio'] ?? 0;
                $name = (string) ($names[$mediaId] ?? '');

                $audit['media'][] = [
                    'volg' => $volg, 'df' => $dilution, 'rep' => $rep, 'name' => $name,
                    'tht' => $assurance->date(3, (string) $mediaId, $mediaId),
                    'set_up_on' => (string) $setUpOn,
                    'set_up_by' => $this->users->name($stepMetadata[$mediaId.'_inzet_user'] ?? null),
                    'read_on' => (string) ($stepMetadata[$mediaId.'_aflees'] ?? ''),
                    'read_by' => $this->users->name($stepMetadata[$mediaId.'_aflees_user'] ?? null),
                ];
                $audit['results'][] = [
                    'volg' => $volg, 'df' => $dilution, 'rep' => $rep, 'name' => $name,
                    'kve' => array_map(fn (int $contender): string => (string) (is_array($contenders[$contender] ?? null) ? ($contenders[$contender][$chain] ?? '') : ''), range(0, self::RACETRACK_COLUMNS - 1)),
                    'tested' => $tested,
                    'positive' => (int) round($tested * (float) $ratio),
                    'positive_control' => $this->control($controls, $setUpOn, $mediaId, 'poscontrol'),
                    'negative_control' => $this->control($controls, $setUpOn, $mediaId, 'negcontrol'),
                    'blank_control' => $this->control($controls, $setUpOn, $mediaId, 'blankcontrol'),
                ];
            }

            foreach ($support as $row) {
                $mediaId = (int) $row['mediaId'];

                if ($this->isInUse($inUse[$df][$rep][$mediaId] ?? false)) {
                    $audit['media'][] = [
                        'volg' => $volg, 'df' => $dilution, 'rep' => $rep, 'name' => (string) ($names[$mediaId] ?? ''),
                        'tht' => $assurance->date(3, (string) $mediaId, $mediaId),
                        'set_up_on' => self::NOT_APPLICABLE, 'set_up_by' => self::NOT_APPLICABLE,
                        'read_on' => self::NOT_APPLICABLE, 'read_by' => self::NOT_APPLICABLE,
                    ];
                }
            }

            if ($global) {
                break;
            }
        }

        return $audit;
    }

    private function isInUse(mixed $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    /** Legacy confKeyStore::fetchValue: nothing without a set-up date or value, but a literal 0 is kept. */
    private function control(mixed $controls, mixed $setUpOn, int $mediaId, string $param): ?string
    {
        if (empty($setUpOn)) {
            return null;
        }

        $value = trim((string) ($controls[$setUpOn.'|'.$mediaId.'|'.$param] ?? ''));

        return $value === '' ? null : $value;
    }
}
