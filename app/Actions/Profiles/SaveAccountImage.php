<?php

namespace App\Actions\Profiles;

use App\Models\User;
use App\Support\AccountImages;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class SaveAccountImage
{
    public function __construct(private UpdateAccountProfile $profiles) {}

    public function handle(User $user, string $kind, UploadedFile $file): void
    {
        $png = $this->normalize($file, $kind === 'avatar');
        $disk = Storage::disk('local');
        $path = $kind === 'avatar'
            ? 'avatars/'.$user->id.'/'.Str::uuid().'.png'
            : AccountImages::signaturePath($user, $kind);

        if (! $disk->put($path, $png)) {
            throw new RuntimeException('Account image could not be saved.');
        }

        if ($kind !== 'avatar') {
            return;
        }

        try {
            $oldPath = DB::transaction(function () use ($user, $path): ?string {
                User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();
                $profile = $this->profiles->ensureProfile($user);
                $oldPath = $profile->avatar_path;
                $profile->update(['avatar_path' => $path]);

                return $oldPath;
            });
        } catch (Throwable $exception) {
            if (! $disk->delete($path)) {
                report(new RuntimeException('Failed to clean up an unsaved avatar.'));
            }

            throw $exception;
        }

        if ($oldPath && ! $disk->delete($oldPath)) {
            report(new RuntimeException('Failed to remove a replaced avatar.'));
        }
    }

    private function normalize(UploadedFile $file, bool $avatar): string
    {
        $source = @imagecreatefromstring($file->getContent());

        if ($source === false) {
            throw ValidationException::withMessages(['image' => 'Deze afbeelding kan niet worden gelezen.']);
        }

        $width = imagesx($source);
        $height = imagesy($source);
        $scale = min(1, 1600 / $width, 600 / $height);
        $targetWidth = $avatar ? 256 : max(1, (int) round($width * $scale));
        $targetHeight = $avatar ? 256 : max(1, (int) round($height * $scale));
        $target = imagecreatetruecolor($targetWidth, $targetHeight);
        imagealphablending($target, false);
        imagesavealpha($target, true);
        $side = min($width, $height);
        imagecopyresampled(
            $target, $source, 0, 0,
            $avatar ? (int) (($width - $side) / 2) : 0,
            $avatar ? (int) (($height - $side) / 2) : 0,
            $targetWidth, $targetHeight,
            $avatar ? $side : $width, $avatar ? $side : $height,
        );

        ob_start();
        try {
            if (! imagepng($target, null, 6)) {
                throw new RuntimeException('Account image could not be encoded.');
            }

            return ob_get_contents();
        } finally {
            ob_end_clean();
        }
    }
}
