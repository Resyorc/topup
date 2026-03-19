<?php

use App\Models\User;

// ── WHITE-BOX: Pastikan coin_balance tidak bisa di-mass assign ────────────────

test('[WHITE-BOX] coin_balance tidak ada di $fillable User model', function () {
    $fillable = (new User)->getFillable();

    expect($fillable)->not->toContain('coin_balance');
});

test('[WHITE-BOX] fill() dengan coin_balance tidak mengubah nilai', function () {
    $user = new User;
    $user->coin_balance = 0;

    $user->fill(['coin_balance' => 999999, 'name' => 'Test User', 'email' => 'test@test.com']);

    // coin_balance harus tetap 0 karena tidak ada di $fillable
    expect($user->coin_balance)->toBe(0);
});
