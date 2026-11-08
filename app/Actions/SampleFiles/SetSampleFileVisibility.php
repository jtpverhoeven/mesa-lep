<?php

namespace App\Actions\SampleFiles;

use App\Actions\Projects\EnsureEditable;
use App\ChangeTracking\ChangeTracker;
use App\ClientPortal\ClientPortalService;
use App\Keyrings\KeyringType;
use App\Models\SampleFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class SetSampleFileVisibility
{
    public function __construct(
        private ClientPortalService $clientPortal,
        private ChangeTracker $changeTracker,
        private EnsureEditable $ensureEditable,
    ) {}

    public function handle(SampleFile $sampleFile, bool $visible): SampleFile
    {
        return DB::transaction(function () use ($sampleFile, $visible): SampleFile {
            $lockedFile = SampleFile::query()->lockForUpdate()->findOrFail($sampleFile->id);
            $this->ensureEditable->handle(null, KeyringType::Sample, $lockedFile->sample_id, applyProjectRule: false);

            if ($visible) {
                $this->storeInPortal($lockedFile);
            } else {
                $this->dropFromPortal($lockedFile);
            }

            $from = $lockedFile->visible_for_client ? 'Zichtbaar' : 'Niet zichtbaar';
            $to = $visible ? 'Zichtbaar' : 'Niet zichtbaar';

            $lockedFile->update(['visible_for_client' => $visible]);
            $this->changeTracker->changed(
                27,
                sample: $lockedFile->sample_id,
                event: 'Bestand zichtbaarheid gewijzigd voor: '.$lockedFile->original_name,
                from: $from,
                to: $to,
            );

            return $lockedFile->fresh();
        });
    }

    private function storeInPortal(SampleFile $sampleFile): void
    {
        $disk = Storage::disk('local');

        if (! $disk->exists($sampleFile->storagePath())) {
            throw ValidationException::withMessages([
                'file' => 'Het opgeslagen bestand kon niet worden gevonden.',
            ]);
        }

        $contents = $disk->get($sampleFile->storagePath());
        $response = $this->clientPortal->storeSampleFileInPortal(
            base64_encode($contents),
            $sampleFile->hash_name,
            md5($contents),
            $sampleFile->client_id,
            $sampleFile->sample_id,
            $sampleFile->original_name,
        );

        if (! $response->successful()) {
            throw ValidationException::withMessages([
                'visible_for_client' => $response->json('message') ?? 'Het bestand kon niet in het klantportaal zichtbaar worden gemaakt.',
            ]);
        }
    }

    private function dropFromPortal(SampleFile $sampleFile): void
    {
        $response = $this->clientPortal->dropSampleFile($sampleFile->sample_id, $sampleFile->hash_name);

        if (! $response->successful()) {
            throw ValidationException::withMessages([
                'visible_for_client' => $response->json('message') ?? 'Het bestand kon niet uit het klantportaal worden verwijderd.',
            ]);
        }
    }
}
