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
use RuntimeException;

class DeleteSampleFile
{
    public function __construct(
        private ClientPortalService $clientPortal,
        private ChangeTracker $changeTracker,
        private EnsureEditable $ensureEditable,
    ) {}

    public function handle(SampleFile $sampleFile): void
    {
        DB::transaction(function () use ($sampleFile): void {
            $lockedFile = SampleFile::query()->lockForUpdate()->findOrFail($sampleFile->id);
            $this->ensureEditable->handle(null, KeyringType::Sample, $lockedFile->sample_id, applyProjectRule: false);
            $response = $this->clientPortal->dropSampleFile($lockedFile->sample_id, $lockedFile->hash_name);

            if (! $response->successful()) {
                throw ValidationException::withMessages([
                    'file' => $response->json('message') ?? 'Het bestand kon niet uit het klantportaal worden verwijderd.',
                ]);
            }

            $disk = Storage::disk('local');

            if ($disk->exists($lockedFile->storagePath()) && ! $disk->delete($lockedFile->storagePath())) {
                throw new RuntimeException('Het opgeslagen bestand kon niet worden verwijderd.');
            }

            $lockedFile->delete();
            $this->changeTracker->changed(
                26,
                sample: $lockedFile->sample_id,
                event: 'Bestand verwijderd',
                from: '',
                to: $lockedFile->original_name,
            );
        });
    }
}
