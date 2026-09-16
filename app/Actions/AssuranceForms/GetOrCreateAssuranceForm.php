<?php

namespace App\Actions\AssuranceForms;

use App\Models\AssuranceForm;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class GetOrCreateAssuranceForm
{
    public function __construct(
        private ResolveAssuranceDay $resolveDay,
        private SynchronizeAssuranceForm $synchronize,
    ) {}

    public function handle(string $formDate): AssuranceForm
    {
        $day = $this->resolveDay->handle($formDate);

        $form = Cache::lock('assurance-form:'.$day['form_date'], 10)->block(5, function () use ($day): AssuranceForm {
            return DB::transaction(function () use ($day): AssuranceForm {
                $form = AssuranceForm::query()
                    ->where('date', (string) $day['timestamp'])
                    ->lockForUpdate()
                    ->first();

                if ($form !== null) {
                    return $form->fresh();
                }

                $availableUsers = User::query()
                    ->where('enabled', true)
                    ->with('profile')
                    ->orderBy('id')
                    ->get(['id', 'name'])
                    ->mapWithKeys(function (User $user): array {
                        $profileName = trim(implode(' ', array_filter([
                            $user->profile?->first_name,
                            $user->profile?->last_name,
                        ])));

                        return [(string) $user->id => $profileName !== '' ? $profileName : (string) $user->name];
                    })
                    ->all();

                $form = AssuranceForm::query()->create([
                    'date' => $day['timestamp'],
                    'is_complete' => false,
                    'data' => json_encode([
                        'users_available_at_time' => $availableUsers,
                        0 => ['ingezet' => '', 'gegoten' => ''],
                        2 => ['tht_pfz' => '', 'tht_pfz_buizen' => '', 'tht_bpw' => ''],
                    ], JSON_FORCE_OBJECT | JSON_THROW_ON_ERROR),
                ]);

                return $form;
            });
        });

        return $this->synchronize->handle($form);
    }
}
