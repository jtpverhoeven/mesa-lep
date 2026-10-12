<?php

namespace App\Actions\LabelDesigns;

use App\Models\LabelDesign;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SaveLabelDesign
{
    /**
     * @param  array{name: string, zpl: string, default_sample: bool|int|string, default_analysis: bool|int|string, default_printer?: int|string|null}  $data
     */
    public function handle(LabelDesign $labelDesign, array $data): LabelDesign
    {
        return Cache::lock('label-designs.defaults', 30)->block(5, function () use ($labelDesign, $data): LabelDesign {
            return DB::transaction(function () use ($labelDesign, $data): LabelDesign {
                $labelDesign->fill($data)->save();

                foreach (['default_sample', 'default_analysis'] as $default) {
                    if ($labelDesign->{$default}) {
                        LabelDesign::query()->whereKeyNot($labelDesign->getKey())->where($default, 1)->update([$default => 0]);
                    }
                }

                return $labelDesign;
            });
        });
    }
}
