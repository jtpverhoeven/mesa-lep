<?php

namespace App\Providers;

use App\Confirmations\ConfirmationAssuranceFields;
use App\Confirmations\PendingConfirmationAssuranceFields;
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
        $this->app->bind(ConfirmationAssuranceFields::class, PendingConfirmationAssuranceFields::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::before(fn (User $user) => $user->hasRole('administrator') ? true : null);
    }
}
