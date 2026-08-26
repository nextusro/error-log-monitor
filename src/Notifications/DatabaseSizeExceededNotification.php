<?php

declare(strict_types=1);

namespace Nextus\ErrorLogMonitor\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DatabaseSizeExceededNotification extends Notification
{
    public function __construct(
        public readonly int $sizeBytes,
        public readonly int $thresholdMb,
        public readonly string $sizeLabel,
    ) {
        $this->locale((string) config('app.locale', 'en'));
    }

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject(trans('error-log-monitor::messages.notifications.database_subject'))
            ->line(trans('error-log-monitor::messages.notifications.database_intro'))
            ->line(trans('error-log-monitor::messages.notifications.database_current_size', ['size' => $this->sizeLabel]))
            ->line(trans('error-log-monitor::messages.notifications.database_threshold', ['threshold' => $this->thresholdMb]));

        if (config('error-log-monitor.route.enabled', true)) {
            $mail->action(
                trans('error-log-monitor::messages.notifications.open_dashboard'),
                route('error-log-monitor.dashboard'),
            );
        }

        return $mail;
    }
}
