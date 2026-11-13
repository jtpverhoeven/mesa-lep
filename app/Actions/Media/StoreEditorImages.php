<?php

namespace App\Actions\Media;

use App\Models\ConfirmationTable;
use App\Models\Worklist;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class StoreEditorImages
{
    private const FOLDERS = [ConfirmationTable::class => 'confirmation-tables', Worklist::class => 'worklists'];

    private const EXTENSIONS = ['image/png' => 'png', 'image/jpeg' => 'jpg', 'image/gif' => 'gif', 'image/webp' => 'webp'];

    public static function directory(Model $owner): string
    {
        return self::FOLDERS[$owner::class].'/'.$owner->getKey();
    }

    /**
     * Moves embedded (base64) and legacy worklist-upload images out of the HTML into private storage
     * and removes stored images that the HTML no longer uses.
     *
     * @param  string|null  $legacyDirectory  Folder with the legacy worklistUpload files.
     */
    public function handle(Model $owner, string $html, ?string $legacyDirectory = null): string
    {
        $html = preg_replace_callback(
            '#(<img\b[^>]*?\bsrc=")(data:image/[a-z+.-]+;base64,([A-Za-z0-9+/=]+))(")#i',
            fn (array $match): string => $match[1].($this->store($owner, base64_decode($match[3], true)) ?? $match[2]).$match[4],
            $html,
        ) ?? $html;

        if ($legacyDirectory !== null) {
            $html = preg_replace_callback(
                '#(<img\b[^>]*?\bsrc=")([^"]*/workLists/loadImage/([A-Za-z0-9]+\.jpg))(")#i',
                fn (array $match): string => $match[1].($this->storeLegacy($owner, $legacyDirectory.DIRECTORY_SEPARATOR.$match[3]) ?? $match[2]).$match[4],
                $html,
            ) ?? $html;
        }

        $this->prune($owner, $html);

        return $html;
    }

    private function storeLegacy(Model $owner, string $path): ?string
    {
        return is_file($path) ? $this->store($owner, file_get_contents($path)) : null;
    }

    private function store(Model $owner, string|false $contents): ?string
    {
        $mime = $contents === false || $contents === '' ? null : (getimagesizefromstring($contents)['mime'] ?? null);
        $extension = self::EXTENSIONS[$mime] ?? null;

        if ($extension === null) {
            return null;
        }

        $name = sha1($contents).'.'.$extension;
        Storage::disk('local')->put(self::directory($owner).'/'.$name, $contents);

        return route(self::FOLDERS[$owner::class].'.images.show', [$owner, $name], false);
    }

    private function prune(Model $owner, string $html): void
    {
        $disk = Storage::disk('local');

        foreach ($disk->files(self::directory($owner)) as $path) {
            if (! str_contains($html, basename($path))) {
                $disk->delete($path);
            }
        }
    }
}
