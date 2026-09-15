<?php

namespace App\Providers;

use App\Models\AuditLog;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Security audit trail (FR-F4) — session events are captured here rather than in
        // the Breeze auth controllers so login/logout logging stays in one place.
        Event::listen(Login::class, function (Login $event) {
            AuditLog::record('auth.login', "{$event->user->name} logged in", $event->user, $event->user->getKey());
        });

        Event::listen(Logout::class, function (Logout $event) {
            if ($event->user) {
                AuditLog::record('auth.logout', "{$event->user->name} logged out", $event->user, $event->user->getKey());
            }
        });
    }
}
