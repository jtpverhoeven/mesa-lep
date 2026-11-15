<?php

namespace App\Http\Responses;

use App\Models\User;
use Illuminate\Http\Request;
use Laravel\Fortify\Http\Responses\LoginResponse as FortifyLoginResponse;
use LogicException;
use Symfony\Component\HttpFoundation\Response;

class PersistDefaultPrinterLoginResponse extends FortifyLoginResponse
{
    /**
     * @param  Request  $request
     */
    public function toResponse($request): Response
    {
        if ($request->filled('printer')) {
            $user = $request->user();

            if (! $user instanceof User) {
                throw new LogicException('A user must be authenticated before saving a printer preference.');
            }

            $user->update(['printer' => $request->integer('printer') ?: null]);
        }

        return parent::toResponse($request);
    }
}
