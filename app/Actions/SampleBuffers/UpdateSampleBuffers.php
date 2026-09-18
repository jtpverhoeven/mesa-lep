<?php

namespace App\Actions\SampleBuffers;

use App\Models\SampleBuffer;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class UpdateSampleBuffers
{
    public function authorizeTht(array $ids, array $values): int
    {
        return DB::transaction(function () use ($ids, $values): int {
            DB::statement("select pg_advisory_xact_lock(hashtext('mesa-lims-tht-code'))");
            $buffers = SampleBuffer::query()->whereKey($ids)->where('tht', 1)->where('authorized', 0)->lockForUpdate()->get();

            foreach ($buffers as $buffer) {
                $buffer->update([
                    'authorized' => 1,
                    'receive_date' => CarbonImmutable::parse($values['receive_date'])->format('d-m-Y'),
                    'receive_time' => $values['receive_time'],
                    'tht_code' => $buffer->tht_code ?: app(ThtCodeGenerator::class)->next(),
                ]);
            }

            return $buffers->count();
        });
    }

    public function handle(array $ids, string $action, array $values): int
    {
        return DB::transaction(function () use ($ids, $action, $values): int {
            $buffers = SampleBuffer::query()->whereKey($ids)->lockForUpdate()->get();

            if ($action === 'delete') {
                return SampleBuffer::query()->whereKey($buffers->modelKeys())->delete();
            }

            foreach ($buffers as $buffer) {
                $this->update($buffer, $action, $values);
            }

            return $buffers->count();
        });
    }

    private function update(SampleBuffer $buffer, string $action, array $values): void
    {
        if ($action === 'sampling_date') {
            $samplingDate = CarbonImmutable::parse($values['date']);
            $buffer->update(['sampling_date' => $samplingDate->format('d-m-Y'), 'project' => 'PRJ-'.$buffer->client.'-'.$samplingDate->format('dmY')]);
        }

        if ($action === 'sampling_method') {
            $buffer->update(['sampling_method' => $values['sampling_method']]);
        }

        if ($action === 'move_to_tht') {
            $buffer->update(['tht' => 1, 'tht_date' => $values['date'], 'misc_directions' => 'Opslag: '.$this->storageLabel($values['storage'])]);
        }

        if ($action === 'tht_date') {
            $buffer->update(['tht_date' => $values['date']]);
        }

        if ($action === 'storage') {
            $directions = preg_replace('/(?:^|\s)Opslag:.*?(?:\.\s*|$)/u', ' ', (string) $buffer->misc_directions);
            $buffer->update(['misc_directions' => trim('Opslag: '.$this->storageLabel($values['storage']).'. '.$directions)]);
        }

        if ($action === 'receive') {
            $buffer->update(['receive_date' => CarbonImmutable::parse($values['receive_date'])->format('d-m-Y'), 'receive_time' => $values['receive_time']]);
        }

        if ($action === 'metadata') {
            $buffer->update(['meta' => $values['meta']]);
        }
    }

    private function storageLabel(string $storage): string
    {
        return match ($storage) {
            '4' => '+3°C',
            '0' => '+4°C',
            '1' => '+7°C',
            '2' => '-18°C',
            '3' => 'Kamer temperatuur',
        };
    }
}
