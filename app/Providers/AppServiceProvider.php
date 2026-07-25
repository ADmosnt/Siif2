<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use App\Models\TProducto;
use App\Models\TPersona;
use App\Observers\PersonaObserver;
use App\Observers\ProductoObserver;
use NotificationChannels\WebPush\Events\NotificationFailed as WebPushNotificationFailed;


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

        // El paquete de webpush trata un envio fallido como un evento de
        // dominio (MessageSentReport con isSuccess()=false), no como una
        // excepcion - sin este listener, un push fallido (VAPID keys mal
        // configuradas, suscripcion vencida, etc.) no deja ningun rastro en
        // storage/logs.
        if (! config('webpush.vapid.public_key') || ! config('webpush.vapid.private_key')) {
            Log::warning('VAPID keys no configuradas: las notificaciones push no se enviaran. Correr "php artisan webpush:vapid".');
        }

        Event::listen(WebPushNotificationFailed::class, function (WebPushNotificationFailed $event) {
            Log::error('Notificacion push fallida', [
                'endpoint' => $event->report->getEndpoint(),
                'reason' => $event->report->getReason(),
                'subscription_expired' => $event->report->isSubscriptionExpired(),
            ]);
        });
    }
}
