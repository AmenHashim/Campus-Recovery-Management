<?php

namespace App\Notifications;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\URL;

/**
 * Replaces Illuminate\Auth\Notifications\VerifyEmail.
 * Dispatched from User::sendEmailVerificationNotification().
 */
class VerifyEmailNotification extends CprmsMailNotification
{
    protected function subject(): string
    {
        return 'Confirm your CPRMS email address';
    }

    protected function content(object $notifiable): array
    {
        return [
            'preview' => 'One click confirms your address and activates your account.',
            'heading' => 'Confirm your email address',
            'greeting' => $this->greet($notifiable),
            'lines' => [
                'Thanks for registering with the University Recovery System Lost & Found Office.',
                'Confirm this address so we can reach you about matches for your reports and updates on your claims.',
            ],
            'actionText' => 'Confirm email address',
            'actionUrl' => $this->verificationUrl($notifiable),
            'outro' => "If you didn't create a CPRMS account, you can ignore this email.",
        ];
    }

    /** Same signed-route contract Laravel's VerifyEmail uses, so the existing route still validates it. */
    private function verificationUrl(object $notifiable): string
    {
        return URL::temporarySignedRoute(
            'verification.verify',
            Carbon::now()->addMinutes(Config::get('auth.verification.expire', 60)),
            [
                'id' => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification()),
            ],
        );
    }
}
