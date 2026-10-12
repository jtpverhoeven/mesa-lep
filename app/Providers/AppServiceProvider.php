<?php

namespace App\Providers;

use App\Confirmations\ConfirmationAssuranceFields;
use App\Confirmations\DatabaseConfirmationAssuranceFields;
use App\Models\User;
use App\Support\Printing\LabelPrinterTransport;
use App\Support\Printing\TcpLabelPrinterTransport;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(ConfirmationAssuranceFields::class, DatabaseConfirmationAssuranceFields::class);
        $this->app->bind(LabelPrinterTransport::class, fn (): LabelPrinterTransport => new TcpLabelPrinterTransport((float) config('printing.timeout_seconds', 5)));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::before(fn (User $user) => $user->hasRole('administrator') ? true : null);
    }
}
