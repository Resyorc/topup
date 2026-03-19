<?php

namespace App\Models;

use Carbon\Carbon;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser, MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, HasRoles, Notifiable, TwoFactorAuthenticatable;

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->hasAnyRole(['Super Admin', 'Admin', 'Staff']);
    }

    public function sendEmailVerificationNotification()
    {
        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            Carbon::now()->addMinutes(60),
            [
                'id' => $this->getKey(),
                'hash' => sha1($this->getEmailForVerification()),
            ]
        );

        Mail::html(
            '
            <div style="font-family: sans-serif; max-width: 600px; margin: 0 auto; padding: 24px;">
                <h2>Verifikasi Email Anda</h2>
                <p>Klik tombol di bawah untuk memverifikasi alamat email Anda.</p>
                <a href="'.$verificationUrl.'"
                   style="display: inline-block; padding: 12px 24px; background-color: #4F46E5;
                          color: white; text-decoration: none; border-radius: 6px; margin: 16px 0;">
                    Verifikasi Email
                </a>
                <p style="color: #666; font-size: 14px;">
                    Link ini akan kadaluarsa dalam 60 menit.<br>
                    Jika Anda tidak merasa mendaftar, abaikan email ini.
                </p>
            </div>
            ',
            function ($message) {
                $message->to($this->email)
                    ->subject('Verifikasi Email - '.config('app.name'));
            }
        );
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'phone',
        'password',
        'google_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }
}
