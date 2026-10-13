<?php

namespace App\Actions\PrintProfiles;

use App\Models\PrintProfile;

class DeletePrintProfile
{
    public function handle(PrintProfile $profile): void
    {
        $profile->delete();
    }
}
