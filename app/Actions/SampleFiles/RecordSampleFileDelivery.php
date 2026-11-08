<?php

namespace App\Actions\SampleFiles;

use App\Models\FileHistory;
use App\Models\SampleFile;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use LogicException;

class RecordSampleFileDelivery
{
    /**
     * @param  array{groups: mixed, extra: mixed}  $recipients
     */
    public function handle(
        SampleFile $sampleFile,
        string $method,
        ?int $transactionId,
        array $recipients,
        ?int $userId = null,
    ): FileHistory {
        if (! in_array($method, ['report', 'filesender'], true)) {
            throw new InvalidArgumentException('Unknown sample file delivery method.');
        }

        $userId ??= auth()->id();

        if ($userId === null) {
            throw new LogicException('A file history requires an authenticated user or an explicit user ID.');
        }

        return DB::transaction(function () use ($sampleFile, $method, $transactionId, $recipients, $userId): FileHistory {
            $lockedFile = SampleFile::query()->lockForUpdate()->findOrFail($sampleFile->id);
            $lockedFile->update(['sent_to_client' => true]);

            return FileHistory::query()->create([
                'sample_id' => $lockedFile->sample_id,
                'samplefile_id' => $lockedFile->id,
                'hash_name' => $lockedFile->hash_name,
                'sent_by' => $userId,
                'sent_to' => $recipients,
                'sent_method' => $method,
                'transaction_id' => $transactionId,
            ]);
        });
    }
}
