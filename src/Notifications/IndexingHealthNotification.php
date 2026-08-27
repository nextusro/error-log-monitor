<?php

declare(strict_types=1);

namespace Nextus\ErrorLogMonitor\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class IndexingHealthNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly bool $recovered,
        public readonly int $processedFiles,
        public readonly int $discoveredFiles,
        public readonly int $pendingFiles,
        public readonly int $partialFiles,
        public readonly int $failedFiles,
        public readonly ?string $reason,
    ) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $key = $this->recovered ? 'indexing_recovered' : 'indexing_incomplete';

        $mail = (new MailMessage)
            ->subject(trans("error-log-monitor::messages.notifications.{$key}_subject"))
            ->line(trans("error-log-monitor::messages.notifications.{$key}_intro"));

        if (! $this->recovered) {
            $mail->line(trans('error-log-monitor::messages.notifications.indexing_progress', [
                'processed' => $this->processedFiles,
                'discovered' => $this->discoveredFiles,
                'pending' => $this->pendingFiles,
                'partial' => $this->partialFiles,
                'failed' => $this->failedFiles,
            ]));
            $mail->line(trans('error-log-monitor::messages.notifications.indexing_reason', [
                'reason' => $this->reason ?? 'unknown',
            ]));
        }

        return $mail->action(
            trans('error-log-monitor::messages.notifications.open_dashboard'),
            route('error-log-monitor.dashboard'),
        );
    }
}
