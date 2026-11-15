<?php

namespace App\Http\Controllers;

use App\Actions\Confirmations\ListRunningConfirmations;
use Illuminate\View\View;

class RunningConfirmationController extends Controller
{
    public function __invoke(ListRunningConfirmations $runningConfirmations): View
    {
        return view('samples.running-confirmations', ['confirmations' => $runningConfirmations->handle()]);
    }
}
