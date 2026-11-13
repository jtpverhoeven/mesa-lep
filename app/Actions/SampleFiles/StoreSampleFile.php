<?php

namespace App\Actions\SampleFiles;

use App\Actions\Projects\EnsureEditable;
use App\ChangeTracking\ChangeTracker;
use App\Keyrings\KeyringType;
use App\Models\Sample;
use App\Models\SampleFile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class StoreSampleFile
{
    public function __construct(
        private ChangeTracker $changeTracker,
        private EnsureEditable $ensureEditable,
        private SetSampleFileVisibility $setVisibility,
        private NormalizeSamplePhoto $normalizePhoto,
    ) {}

    public function handle(
        Sample $sample,
        UploadedFile $uploadedFile,
        bool $photoMode = false,
        bool $instantVisible = false,
    ): SampleFile {
        $storedPath = null;
        $photo = $photoMode ? $this->normalizePhoto->handle($uploadedFile) : null;

        try {
            return DB::transaction(function () use ($sample, $uploadedFile, $photoMode, $instantVisible, $photo, &$storedPath): SampleFile {
                $lockedSample = Sample::query()->lockForUpdate()->findOrFail($sample->id);
                $this->ensureEditable->handle(null, KeyringType::Sample, $lockedSample->id, applyProjectRule: false);
                $originalName = $this->safeOriginalName($uploadedFile);

                if ($photoMode) {
                    $originalName = $this->photoName($lockedSample, 'jpg');
                }

                $hashName = (string) Str::uuid();
                $storedPath = SampleFile::STORAGE_DIRECTORY.'/'.$hashName;
                $saved = $photoMode
                    ? Storage::disk('local')->put($storedPath, $photo)
                    : $uploadedFile->storeAs(SampleFile::STORAGE_DIRECTORY, $hashName, 'local');

                if ($saved === false) {
                    throw new RuntimeException('Het bestand kon niet worden opgeslagen.');
                }

                $sampleFile = SampleFile::query()->create([
                    'sample_id' => $lockedSample->id,
                    'client_id' => $lockedSample->client,
                    'hash_name' => $hashName,
                    'original_name' => $originalName,
                    'visible_for_client' => false,
                    'sent_to_client' => false,
                ]);

                $this->changeTracker->changed(
                    25,
                    sample: $lockedSample->id,
                    event: 'Bestand geupload',
                    from: '',
                    to: $originalName,
                );

                return $instantVisible
                    ? $this->setVisibility->handle($sampleFile, true)
                    : $sampleFile;
            });
        } catch (Throwable $exception) {
            if ($storedPath !== null) {
                Storage::disk('local')->delete($storedPath);
            }

            throw $exception;
        }
    }

    private function safeOriginalName(UploadedFile $uploadedFile): string
    {
        $name = basename(str_replace('\\', '/', $uploadedFile->getClientOriginalName()));

        return $name !== '' ? $name : 'bestand';
    }

    private function photoName(Sample $sample, string $extension): string
    {
        $usedNames = $sample->sampleFiles()
            ->lockForUpdate()
            ->pluck('original_name')
            ->map(fn (string $name): string => pathinfo($name, PATHINFO_FILENAME))
            ->all();
        $followNumber = 1;

        while (in_array($sample->barcode.'_'.$followNumber, $usedNames, true)) {
            $followNumber++;
        }

        return $sample->barcode.'_'.$followNumber.'.'.$extension;
    }
}
