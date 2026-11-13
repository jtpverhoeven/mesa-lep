<?php

namespace App\Actions\SampleFiles;

use App\Support\Cvars;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use Imagick;
use ImagickException;
use ImagickPixel;
use RuntimeException;

class NormalizeSamplePhoto
{
    public function __construct(private Cvars $cvars) {}

    public function maxEdge(): int
    {
        $configuredEdge = $this->cvars->valueOrDefault('SAMPLE_PHOTO_MAX_EDGE', '2048');

        if (! ctype_digit((string) $configuredEdge) || (int) $configuredEdge < 256 || (int) $configuredEdge > 8192) {
            throw new RuntimeException('SAMPLE_PHOTO_MAX_EDGE moet tussen 256 en 8192 pixels liggen.');
        }

        return (int) $configuredEdge;
    }

    public function handle(UploadedFile $file): string
    {
        $maxEdge = $this->maxEdge();
        $source = $file->getRealPath().'[0]';

        try {
            $image = new Imagick;
            $image->pingImage($source);

            if ($image->getImageWidth() * $image->getImageHeight() > 40_000_000) {
                throw ValidationException::withMessages(['file' => 'De foto is te groot (maximaal 40 megapixels).']);
            }

            $image->clear();
            $image->readImage($source);
            $image->autoOrient();
            $image->transformImageColorspace(Imagick::COLORSPACE_SRGB);
            $image->setImageBackgroundColor(new ImagickPixel('white'));
            $flattened = $image->mergeImageLayers(Imagick::LAYERMETHOD_FLATTEN);
            $image->clear();

            if (max($flattened->getImageWidth(), $flattened->getImageHeight()) > $maxEdge) {
                $flattened->thumbnailImage($maxEdge, $maxEdge, true);
            }

            $flattened->stripImage();
            $flattened->setImageFormat('jpeg');
            $flattened->setImageCompressionQuality(90);
            $jpeg = $flattened->getImageBlob();
            $flattened->clear();

            if ($jpeg === '') {
                throw new RuntimeException('De foto kon niet als JPEG worden opgeslagen.');
            }

            return $jpeg;
        } catch (ImagickException $exception) {
            report($exception);
            throw ValidationException::withMessages(['file' => 'Deze foto kan niet worden gelezen of omgezet.']);
        }
    }
}
