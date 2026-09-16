<?php

namespace App\Providers;

use App\Confirmations\ConfirmationAssuranceFields;
use App\Confirmations\DatabaseConfirmationAssuranceFields;
use App\Models\User;
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
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::before(fn (User $user) => $user->hasRole('administrator') ? true : null);
    }
}
