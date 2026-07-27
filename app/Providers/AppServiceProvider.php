<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\TProducto;
use App\Models\TPersona;
use App\Observers\PersonaObserver;
use App\Observers\ProductoObserver;


class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Aquí puedes registrar servicios adicionales si es necesario
        // Por ejemplo, puedes registrar un servicio de autenticación personalizado
        // $this->app->singleton('AuthService', function ($app) {
        //     return new AuthService();
        // });
        
        // También puedes registrar bindings para interfaces y clases concretas
        // $this->app->bind('SomeInterface', 'SomeConcreteClass');
    }

    /**
     * Bootstrap any application services.
     * Aqui se definen los gates, para administrar la permisologia segun los roles de usuario
     */
    public function boot(): void
    {
        TProducto::observe(ProductoObserver::class);
        TPersona::observe(PersonaObserver::class);
    }
}
