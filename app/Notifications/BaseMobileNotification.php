<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use NotificationChannels\Fcm\FcmChannel;
use NotificationChannels\Fcm\FcmMessage;
use NotificationChannels\Fcm\Resources\Notification as FcmNotification;

abstract class BaseMobileNotification extends Notification
{
    public function via($notifiable): array
    {
        return [FcmChannel::class];
    }

    abstract protected function title(): string;

    abstract protected function body(): string;

    abstract protected function data(): array;

    public function toFcm($notifiable): FcmMessage
    {
        return FcmMessage::create()
            ->data($this->data())
            ->notification(FcmNotification::create()->title($this->title())->body($this->body()));
    }
}
