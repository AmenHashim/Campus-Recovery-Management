<?php

namespace App\Notifications;

use Illuminate\Support\Facades\Config;

/**
 * Replaces Illuminate\Auth\Notifications\ResetPassword (FR-A3).
 * Dispatched from User::sendPasswordResetNotification().
 */
class ResetPasswordNotification extends CprmsMailNotification
{
    public function __construct(public string $token)
    {
    }

    protected function subject(): string
    {
        return 'Reset your CPRMS password';
    }

    protected function content(object $notifiable): array
    {
        $minutes = Config::get('auth.passwords.'.Config::get('auth.defaults.passwords').'.expire', 60);

        return [
            'preview' => 'Use the link inside to choose a new password.',
            'heading' => 'Password reset request',
            'greeting' => $this->greet($notifiable),
            'lines' => [
                'We received a request to reset the password for your CPRMS account ('.$notifiable->email.').',
                'Choose a new password using the button below. The link expires in '.$minutes.' minutes.',
            ],
            'actionText' => 'Reset password',
            'actionUrl' => $this->resetUrl($notifiable),
            'outro' => "If you didn't ask for this, no action is needed — your password stays as it is. "
                .'If you keep getting these emails, tell the Lost & Found Office so they can check the account.',
        ];
    }

    private function resetUrl(object $notifiable): string
    {
        return url(route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], absolute: false));
    }
}
