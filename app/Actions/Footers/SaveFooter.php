<?php

namespace App\Actions\Footers;

use App\Models\Footer;

class SaveFooter
{
    /** @param array{name: string, lang: string, q: int|string, data: string} $data */
    public function handle(Footer $footer, array $data): Footer
    {
        $footer->fill($data)->save();

        return $footer;
    }
}
