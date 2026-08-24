<?php

namespace App\Notifications;

use App\Models\Customer;
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

    protected function channelId($notifiable): string
    {
        if ($notifiable instanceof Customer) {
            return 'billnet_customer';
        }

        return 'billnet_admin';
    }

    public function toFcm($notifiable): FcmMessage
    {
        $sanitizedData = array_map(function ($value) {
            return is_null($value) ? '' : (is_scalar($value) ? (string) $value : json_encode($value));
        }, $this->data());

        $channelId = $this->channelId($notifiable);

        return FcmMessage::create()
            ->data($sanitizedData)
            ->notification(FcmNotification::create()->title($this->title())->body($this->body()))
            ->android([
                'priority' => 'high',
                'notification' => [
                    'channel_id' => $channelId,
                    'sound' => 'default',
                    'default_sound' => true,
                    'default_vibrate_timings' => true,
                ],
            ]);
    }
}
