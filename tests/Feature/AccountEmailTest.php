<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use App\Notifications\VerifyEmailNotification;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Account email is CPRMS's own, not the framework's default template.
 */
class AccountEmailTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    protected function render(object $notification, User $user): string
    {
        return (string) $notification->toMail($user)->render();
    }

    public function test_password_reset_email_is_cprms_branded_and_links_to_the_reset_form(): void
    {
        $user = User::factory()->create(['name' => 'Biggie Kasese']);
        $notification = new ResetPasswordNotification('test-token');

        $mail = $notification->toMail($user);
        $this->assertSame('Reset your CPRMS password', $mail->subject);

        $html = $this->render($notification, $user);

        $this->assertStringContainsString('CPRMS', $html);
        $this->assertStringContainsString('University Recovery System', $html);
        $this->assertStringContainsString('Hello Biggie,', $html);
        $this->assertStringContainsString(route('password.reset', [
            'token' => 'test-token',
            'email' => $user->email,
        ], absolute: false), $html);

        // The giveaway strings from Laravel's stock notification template.
        $this->assertStringNotContainsString('Regards,', $html);
        $this->assertStringNotContainsString('laravel.com', $html);
    }

    public function test_verification_email_is_cprms_branded_and_carries_a_signed_link(): void
    {
        $user = User::factory()->unverified()->create();
        $notification = new VerifyEmailNotification;

        $this->assertSame('Confirm your CPRMS email address', $notification->toMail($user)->subject);

        $html = $this->render($notification, $user);

        $this->assertStringContainsString('Confirm email address', $html);
        $this->assertStringContainsString('signature=', $html);
        $this->assertStringContainsString('/verify-email/'.$user->id.'/'.sha1($user->email), $html);
    }

    public function test_the_user_model_sends_the_cprms_notifications(): void
    {
        \Illuminate\Support\Facades\Notification::fake();

        $user = User::factory()->unverified()->create();

        $user->sendPasswordResetNotification('token');
        $user->sendEmailVerificationNotification();

        \Illuminate\Support\Facades\Notification::assertSentTo($user, ResetPasswordNotification::class);
        \Illuminate\Support\Facades\Notification::assertSentTo($user, VerifyEmailNotification::class);
    }
}
