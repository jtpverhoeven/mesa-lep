<?php

namespace App\Calculations;

use App\Models\Assay;
use App\Models\AssayProfile;
use App\Models\RoamingAnalysis;
use App\Models\SampleAnalysis;

final readonly class ResultCalculationContext
{
    public function __construct(
        public SampleAnalysis $analysis,
        public Assay $assay,
        public AssayProfile|RoamingAnalysis $settings,
    ) {}
}
