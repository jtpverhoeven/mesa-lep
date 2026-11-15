<?php

namespace App\Providers;

use App\Actions\Keyrings\ReleaseUserKeyrings;
use App\Actions\Sidebar\GetSidebarCounts;
use App\Confirmations\ConfirmationAssuranceFields;
use App\Confirmations\DatabaseConfirmationAssuranceFields;
use App\Models\Project;
use App\Models\Sample;
use App\Models\User;
use App\Support\Cvars;
use App\Support\Printing\LabelPrinterTransport;
use App\Support\Printing\TcpLabelPrinterTransport;
use Illuminate\Auth\Events\Logout;
use Illuminate\Contracts\View\View as ViewContract;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->scoped(Cvars::class);
        $this->app->bind(ConfirmationAssuranceFields::class, DatabaseConfirmationAssuranceFields::class);
        $this->app->bind(LabelPrinterTransport::class, fn (): LabelPrinterTransport => new TcpLabelPrinterTransport((float) config('printing.timeout_seconds', 5)));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Relation::morphMap(['project' => Project::class, 'sample' => Sample::class]);

        Gate::before(fn (User $user) => $user->hasRole('administrator') ? true : null);

        View::composer('layouts.app', function (ViewContract $view): void {
            if (auth()->check()) {
                $view->with('sidebarCounts', app(GetSidebarCounts::class)->handle());
            }
        });

        Event::listen(function (Logout $event): void {
            if ($event->user instanceof User) {
                app(ReleaseUserKeyrings::class)->handle($event->user);
            }
        });
    }
}
