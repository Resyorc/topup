<?php

use App\Models\User;
use Illuminate\Support\Facades\Mail;

test('sends verification notification', function () {
    Mail::fake();

    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->post(route('verification.send'))
        ->assertRedirect(route('home'));

    Mail::assertSentCount(1);
});

test('does not send verification notification if email is verified', function () {
    Mail::fake();

    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('verification.send'))
        ->assertRedirect(route('dashboard', absolute: false));

    Mail::assertNothingSent();
});
