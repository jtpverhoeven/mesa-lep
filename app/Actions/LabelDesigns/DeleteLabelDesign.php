<?php

namespace App\Actions\LabelDesigns;

use App\Models\LabelDesign;

class DeleteLabelDesign
{
    public function handle(LabelDesign $labelDesign): void
    {
        $labelDesign->delete();
    }
}
