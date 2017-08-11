<?php

namespace NotificationChannels\OneSignal;

use Berkayk\OneSignal\OneSignalClient;
use NotificationChannels\OneSignal\Exceptions\CouldNotSendNotification;
use Illuminate\Notifications\Notification;
use Psr\Http\Message\ResponseInterface;

class OneSignalChannel
{
    /** @var OneSignalClient */
    protected $oneSignal;

    public function __construct(OneSignalClient $oneSignal)
    {
        $this->oneSignal = $oneSignal;
    }

    /**
     * Send the given notification.
     *
     * @param mixed $notifiable
     * @param \Illuminate\Notifications\Notification $notification
     *
     * @throws \NotificationChannels\OneSignal\Exceptions\CouldNotSendNotification
     */
    public function send($notifiable, Notification $notification)
    {
        if (! $userIds = $notifiable->routeNotificationFor('OneSignal')) {
            return;
        }

        $payload = $notification->toOneSignal($notifiable)->toArray();
        $payload['include_player_ids'] = collect($userIds);

        // Only way to add custom app_id. (see issue: https://github.com/berkayk/laravel-onesignal/issues/33)
        if(isset($payload['app_id'])) {
            $this->oneSignal->setParam('app_id', $payload['app_id']);
            unset($payload['app_id']);
        }

        /** @var ResponseInterface $response */
        $response = $this->oneSignal->sendNotificationCustom($payload);

        if ($response->getStatusCode() !== 200) {
            throw CouldNotSendNotification::serviceRespondedWithAnError($response);
        }
    }
}
