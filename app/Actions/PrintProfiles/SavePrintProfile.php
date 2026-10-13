<?php

namespace App\Actions\PrintProfiles;

use App\Models\PrintProfile;

class SavePrintProfile
{
    public function __construct(private ParsePrintProfile $parsePrintProfile) {}

    /** @param array{name: string, profile: string} $data */
    public function handle(PrintProfile $profile, array $data): PrintProfile
    {
        $this->parsePrintProfile->handle($data['profile']);
        $profile->fill(['name' => $data['name'], 'profile' => $data['profile']])->save();

        return $profile;
    }
}
