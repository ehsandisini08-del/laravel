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
        $sanitizedData = array_map(function ($value) {
            return is_null($value) ? '' : (is_scalar($value) ? (string) $value : json_encode($value));
        }, $this->data());

        return FcmMessage::create()
            ->data($sanitizedData)
            ->notification(FcmNotification::create()->title($this->title())->body($this->body()))
            ->android([
                'priority' => 'high',
                'notification' => [
                    'channel_id' => 'billnet_customer',
                ],
            ]);
    }
}
