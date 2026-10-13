<?php

namespace App\Actions\PrintProfiles;

use InvalidArgumentException;

class ParsePrintProfile
{
    public const MAX_COPIES = 9999;

    /** @return array<int, array{command: string, mode?: string, ids?: list<int>, label?: int, printer?: int, copies?: int, single?: bool, goto?: int, else?: int|null, message?: string}> */
    public function handle(string $script): array
    {
        if (strlen($script) > 65535) {
            throw new InvalidArgumentException('Het profiel is te groot (maximaal 65535 bytes).');
        }

        $instructions = [];

        foreach (preg_split('/\r\n|\r|\n/', $script) as $sourceLine => $text) {
            $text = trim($text);

            if ($text === '' || str_starts_with($text, '#')) {
                continue;
            }

            if (! preg_match('/^([1-9]\d*)\s+(.+)$/', $text, $parts)) {
                throw new InvalidArgumentException('Regel '.($sourceLine + 1).': een regelnummer en instructie zijn verplicht.');
            }

            $line = (int) $parts[1];

            if (isset($instructions[$line])) {
                throw new InvalidArgumentException('Regel '.$line.': dubbel regelnummer.');
            }

            $instructions[$line] = $this->instruction($parts[2], $line);

            if (count($instructions) > 1000) {
                throw new InvalidArgumentException('Het profiel bevat meer dan 1000 instructies.');
            }
        }

        if ($instructions === []) {
            throw new InvalidArgumentException('Het profiel bevat geen instructies.');
        }

        foreach ($instructions as $line => $instruction) {
            foreach (['goto', 'else'] as $branch) {
                if (isset($instruction[$branch]) && ! isset($instructions[$instruction[$branch]])) {
                    throw new InvalidArgumentException('Regel '.$line.': doelregel '.$instruction[$branch].' bestaat niet.');
                }
            }
        }

        return $instructions;
    }

    /** @return array{command: string, mode?: string, ids?: list<int>, label?: int, printer?: int, copies?: int, single?: bool, goto?: int, else?: int|null, message?: string} */
    private function instruction(string $text, int $line): array
    {
        if ($text === 'EXIT') {
            return ['command' => 'EXIT'];
        }

        if (preg_match('/^(LOG|COMMENT)(?:\s+(.*))?$/', $text, $parts)) {
            return ['command' => $parts[1], 'message' => $parts[2] ?? ''];
        }

        if (preg_match('/^GOTO\s+([1-9]\d*)$/', $text, $parts)) {
            return ['command' => 'GOTO', 'goto' => (int) $parts[1]];
        }

        if (preg_match('/^SAMPLE\s+HASONLYONE\s+GOTO\s+([1-9]\d*)(?:\s+ELSE\s+([1-9]\d*))?$/', $text, $parts)) {
            return ['command' => 'SAMPLE', 'mode' => 'HASONLYONE', 'goto' => (int) $parts[1], 'else' => isset($parts[2]) ? (int) $parts[2] : null];
        }

        if (preg_match('/^SAMPLE\s+(HASANY|HASALL|HASANYWITHCONFIRMATION)\s+(\[.*?\]|\{.*?\})\s+GOTO\s+([1-9]\d*)(?:\s+ELSE\s+([1-9]\d*))?$/', $text, $parts)) {
            return ['command' => 'SAMPLE', 'mode' => $parts[1], 'ids' => $this->ids($parts[2], $line), 'goto' => (int) $parts[3], 'else' => isset($parts[4]) ? (int) $parts[4] : null];
        }

        if (preg_match('/^SAMPLE\s+PRINT\s+(\d+)\s+\S+\s+(\d+)\s+\S+\s+(\d+)$/', $text, $parts)) {
            return ['command' => 'SAMPLE', 'mode' => 'PRINT', ...$this->printing((int) $parts[1], (int) $parts[2], (int) $parts[3], $line)];
        }

        if (preg_match('/^ANALYSIS\s+ANY\s+\S+\s+(\d+)\s+\S+\s+(\d+)\s+\S+\s+(\d+)(?:\s+(SINGLE))?$/', $text, $parts)) {
            return ['command' => 'ANALYSIS', 'mode' => 'ANY', 'single' => isset($parts[4]), ...$this->printing((int) $parts[1], (int) $parts[2], (int) $parts[3], $line)];
        }

        if (preg_match('/^ANALYSIS\s+(NOT|ONLY|ONLYWITHCONFIRMATION|GROUP|NOTGROUP)\s+(\[.*?\]|\{.*?\})\s+\S+\s+(\d+)\s+\S+\s+(\d+)\s+\S+\s+(\d+)(?:\s+(SINGLE))?$/', $text, $parts)) {
            return ['command' => 'ANALYSIS', 'mode' => $parts[1], 'ids' => $this->ids($parts[2], $line), 'single' => isset($parts[6]), ...$this->printing((int) $parts[3], (int) $parts[4], (int) $parts[5], $line)];
        }

        throw new InvalidArgumentException('Regel '.$line.': ongeldige instructie.');
    }

    /** @return list<int> */
    private function ids(string $json, int $line): array
    {
        $values = json_decode($json, true);

        if (! is_array($values)) {
            throw new InvalidArgumentException('Regel '.$line.': ongeldige JSON-lijst.');
        }

        $ids = [];

        foreach ($values as $value) {
            if ((! is_int($value) && ! is_string($value)) || ! preg_match('/^[1-9]\d*$/', (string) $value)) {
                throw new InvalidArgumentException('Regel '.$line.': de lijst moet positieve IDs bevatten.');
            }

            $ids[] = (int) $value;
        }

        return $ids;
    }

    /** @return array{label: int, printer: int, copies: int} */
    private function printing(int $label, int $printer, int $copies, int $line): array
    {
        if ($label < 1 || $copies < 1 || $copies > self::MAX_COPIES) {
            throw new InvalidArgumentException('Regel '.$line.': ongeldige label-ID of aantal (1-'.self::MAX_COPIES.').');
        }

        return ['label' => $label, 'printer' => $printer, 'copies' => $copies];
    }
}
