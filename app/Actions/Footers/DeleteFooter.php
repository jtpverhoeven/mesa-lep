<?php

namespace App\Actions\Footers;

use App\Models\Footer;

class DeleteFooter
{
    public function handle(Footer $footer): void
    {
        $footer->delete();
    }
}
