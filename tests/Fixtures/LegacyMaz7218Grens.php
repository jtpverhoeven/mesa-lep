<?php

namespace {
    require_once __DIR__.'/../../legacy/app/shared/calculation.class.php';
    require_once __DIR__.'/../../legacy/app/private/templateScripts/maz7218grens.class.php';

    if (! defined('MAZ_SALM_IDS')) {
        define('MAZ_SALM_IDS', '[]');
    }

    if (! function_exists('checkKeyOrFalse')) {
        function checkKeyOrFalse(mixed $array, string|int ...$keys): mixed
        {
            foreach ($keys as $key) {
                if (! isset($array[$key])) {
                    return false;
                }

                $array = $array[$key];
            }

            return $array;
        }
    }

    if (! function_exists('checkKeyOrBlank')) {
        function checkKeyOrBlank(mixed $array, string|int ...$keys): mixed
        {
            foreach ($keys as $key) {
                if (! isset($array[$key])) {
                    return '';
                }

                $array = $array[$key];
            }

            return $array;
        }
    }
}

namespace Tests\Fixtures {
    use App\Calculations\ResultCalculationContext;

    class LegacyMaz7218Grens extends \maz7218grens
    {
        public function __construct(ResultCalculationContext $context, array $variables)
        {
            $this->output = ['output' => [], 'outputEn' => []];
            $this->externalVariables = $variables;
            $this->usesReplicates = $context->assay->replicates;
            $this->usesConfirmation = (int) $context->assay->confirmation === 1;
            $this->confirmationType = $context->assay->confirmation_type;
            $this->confRequested = $context->analysis->conf_requested;
            $this->confirmationDone = $context->confirmation?->ready ?? false;
            $this->confirmationRatios = json_decode(json_encode($context->confirmation?->ratios ?? [], JSON_THROW_ON_ERROR), true);
            $this->originalAssayId = 1;
            $this->reportInHumanReadable = json_decode($context->assay->custom_fields ?? '{}', true)['resultin'] ?? 'kve';
            $this->authorisedSample = $context->analysis->relationLoaded('projectRecord') && (bool) $context->analysis->projectRecord?->auth_status;

            foreach ($context->analysis->results->sortByDesc('df') as $result) {
                if ((int) $result->rep === 0) {
                    $this->results[(string) $result->df] = $result->data;
                }
            }
        }

        protected function _dispatch(): array
        {
            return [
                'output' => $this->output['output'],
                'outputEn' => $this->output['outputEn'],
                'disposition' => $this->disposition,
                'messageBag' => $this->messageBag,
            ];
        }
    }
}
