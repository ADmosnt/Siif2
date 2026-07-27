<?php

namespace App\Notifications;

use Illuminate\Notification\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class SiifPushNotification extends Notification
{
    public function __construct(
        protected string $titulo,
        protected string $mensaje,
        protected ?int $idNotificacion = null
    ) {}

    public function via($notifiable): array
    {
        return [WebPushChannel::class];
    }

    public function toWebPush($notifiable, $notification): WebPushMessage
    {
        return (new WebPushMessage)
            ->title($this->titulo)
            ->icon('/pwa-192x192.png')
            ->body($this->mensaje)
            ->badge('/pwa-192x192.png')
            ->tag('siif-notificacion-' . ($this->idNotificacion ?? 'general'))
            ->data([
                'id' => $this->idNotificacion,
                'url' => '/notificacion',
            ]);
    }
}
