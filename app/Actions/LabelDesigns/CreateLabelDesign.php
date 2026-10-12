<?php

namespace App\Actions\LabelDesigns;

use App\Models\LabelDesign;

class CreateLabelDesign
{
    public function __construct(private SaveLabelDesign $saveLabelDesign) {}

    /**
     * @param  array{name: string, zpl: string, default_sample: bool|int|string, default_analysis: bool|int|string, default_printer?: int|string|null}  $data
     */
    public function handle(array $data): LabelDesign
    {
        return $this->saveLabelDesign->handle(new LabelDesign, $data);
    }
}
