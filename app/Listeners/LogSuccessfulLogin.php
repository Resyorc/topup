<?php

namespace App\Listeners;

use App\Services\AuditLogger;
use Illuminate\Auth\Events\Login;

class LogSuccessfulLogin
{
    public function handle(Login $event): void
    {
        AuditLogger::log(
            event: 'login',
            description: 'User login berhasil.',
            userId: $event->user->id,
        );
    }
}
