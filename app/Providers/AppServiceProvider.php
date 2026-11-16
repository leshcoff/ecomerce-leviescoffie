<?php

namespace App\Providers;
use Illuminate\Support\ServiceProvider;
use App\Http\Middleware\EsAdmin;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
        $this->app->singleton(\App\Services\TipoCambioService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Las peticiones /livewire/update de componentes del admin también exigen ser administrador
        Livewire::addPersistentMiddleware([EsAdmin::class]);
    }
}
