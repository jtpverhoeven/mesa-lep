<?php

namespace App\Actions\ConfirmationTables;

use App\Models\ConfirmationTable;
use Illuminate\Support\Facades\Storage;

class StoreConfirmationTableImages
{
    private const EXTENSIONS = ['image/png' => 'png', 'image/jpeg' => 'jpg', 'image/gif' => 'gif', 'image/webp' => 'webp'];

    public static function directory(ConfirmationTable $table): string
    {
        return 'confirmation-tables/'.$table->id;
    }

    /**
     * Moves embedded (base64) and legacy worklist images out of the HTML into private storage
     * and removes stored images that the HTML no longer uses.
     *
     * @param  string|null  $legacyDirectory  Folder with the legacy worklistUpload files.
     */
    public function handle(ConfirmationTable $table, string $html, ?string $legacyDirectory = null): string
    {
        $html = preg_replace_callback(
            '#(<img\b[^>]*?\bsrc=")(data:image/[a-z+.-]+;base64,([A-Za-z0-9+/=]+))(")#i',
            fn (array $match): string => $match[1].($this->store($table, base64_decode($match[3], true)) ?? $match[2]).$match[4],
            $html,
        ) ?? $html;

        if ($legacyDirectory !== null) {
            $html = preg_replace_callback(
                '#(<img\b[^>]*?\bsrc=")([^"]*/workLists/loadImage/([A-Za-z0-9]+\.jpg))(")#i',
                fn (array $match): string => $match[1].($this->storeLegacy($table, $legacyDirectory.DIRECTORY_SEPARATOR.$match[3]) ?? $match[2]).$match[4],
                $html,
            ) ?? $html;
        }

        $this->prune($table, $html);

        return $html;
    }

    private function storeLegacy(ConfirmationTable $table, string $path): ?string
    {
        return is_file($path) ? $this->store($table, file_get_contents($path)) : null;
    }

    private function store(ConfirmationTable $table, string|false $contents): ?string
    {
        $mime = $contents === false || $contents === '' ? null : (getimagesizefromstring($contents)['mime'] ?? null);
        $extension = self::EXTENSIONS[$mime] ?? null;

        if ($extension === null) {
            return null;
        }

        $name = sha1($contents).'.'.$extension;
        Storage::disk('local')->put(self::directory($table).'/'.$name, $contents);

        return route('confirmation-tables.images.show', [$table, $name], false);
    }

    private function prune(ConfirmationTable $table, string $html): void
    {
        $disk = Storage::disk('local');

        foreach ($disk->files(self::directory($table)) as $path) {
            if (! str_contains($html, basename($path))) {
                $disk->delete($path);
            }
        }
    }
}
