<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Base for every CPRMS email.
 *
 * Laravel's own notifications render through its generic markdown template, which
 * carries framework branding and reads nothing like the rest of the system. These
 * render through resources/views/emails/layout.blade.php instead — one CPRMS shell,
 * HTML + plain-text, with the subclass supplying only the content.
 */
abstract class CprmsMailNotification extends Notification
{
    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    abstract protected function subject(): string;

    /**
     * Content for the shared shell.
     *
     * @return array{heading: string, greeting?: string, lines: array<int, string>,
     *               actionText?: string, actionUrl?: string, outro?: string, preview?: string}
     */
    abstract protected function content(object $notifiable): array;

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->subject())
            ->view(['emails.layout', 'emails.layout-plain'], $this->content($notifiable));
    }

    /** First name only — "Hello Biggie," reads better than the full registered name. */
    protected function greet(object $notifiable): string
    {
        $first = strtok((string) ($notifiable->name ?? ''), ' ');

        return $first ? "Hello {$first}," : 'Hello,';
    }
}
