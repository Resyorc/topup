<?php

namespace App\Listeners;

use App\Services\AuditLogger;
use Illuminate\Auth\Events\Registered;

class LogRegistered
{
    public function handle(Registered $event): void
    {
        AuditLogger::log(
            event: 'register',
            description: 'Akun baru dibuat: '.$event->user->email,
            userId: $event->user->id,
        );
    }
}
