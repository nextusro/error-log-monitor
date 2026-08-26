<?php

declare(strict_types=1);

namespace Nextus\ErrorLogMonitor\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class IssueNotification extends Notification
{
    public function __construct(
        public readonly int $issueId,
        public readonly string $event,
        public readonly string $level,
        public readonly string $message,
        public readonly ?string $exceptionClass,
        public readonly ?string $filePath,
        public readonly int $occurrencesCount,
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
        $key = $this->event === 'regression' ? 'regression' : 'new_issue';

        $mail = (new MailMessage)
            ->subject(trans("error-log-monitor::messages.notifications.{$key}_subject", [
                'level' => strtoupper($this->level),
            ]))
            ->line(trans("error-log-monitor::messages.notifications.{$key}_intro"))
            ->line(trans('error-log-monitor::messages.notifications.issue_level', ['level' => strtoupper($this->level)]))
            ->line(trans('error-log-monitor::messages.notifications.issue_message', ['message' => $this->message]))
            ->line(trans('error-log-monitor::messages.notifications.issue_occurrences', ['count' => $this->occurrencesCount]));

        if ($this->exceptionClass !== null) {
            $mail->line(trans('error-log-monitor::messages.notifications.issue_exception', ['exception' => $this->exceptionClass]));
        }

        if ($this->filePath !== null) {
            $mail->line(trans('error-log-monitor::messages.notifications.issue_file', ['file' => $this->filePath]));
        }

        if (config('error-log-monitor.route.enabled', true)) {
            $mail->action(
                trans('error-log-monitor::messages.notifications.view_issue'),
                route('error-log-monitor.dashboard', ['issue_id' => $this->issueId, 'status' => 'all']).'#issue-'.$this->issueId,
            );
        }

        return $mail;
    }
}
