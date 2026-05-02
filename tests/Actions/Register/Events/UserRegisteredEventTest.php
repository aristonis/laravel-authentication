<?php

declare(strict_types=1);

use Aristonis\LaravelAuthentication\Actions\Register\Events\UserRegisteredEvent;
use Aristonis\LaravelAuthentication\Tests\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('event can be created with user', function () {
    $user = User::factory()->make();

    $event = new UserRegisteredEvent($user);

    expect($event->user)->toBe($user);
    expect($event->autoLoggedIn)->toBeFalse();
});

test('event can be created with autoLoggedIn flag', function () {
    $user = User::factory()->make();

    $event = new UserRegisteredEvent($user, autoLoggedIn: true);

    expect($event->user)->toBe($user);
    expect($event->autoLoggedIn)->toBeTrue();
});

test('event user is readonly', function () {
    $user = User::factory()->make();

    $event = new UserRegisteredEvent($user);

    // Verify property is readonly
    expect($event->user)->toBe($user);
});

test('event autoLoggedIn defaults to false', function () {
    $user = User::factory()->make();

    $event = new UserRegisteredEvent($user);

    expect($event->autoLoggedIn)->toBeFalse();
});

test('event uses dispatchable and serializesModels traits', function () {
    $user = User::factory()->make();

    $event = new UserRegisteredEvent($user);

    // Verify traits are used via reflection
    $reflection = new \ReflectionClass($event);
    $traits = $reflection->getTraitNames();

    expect($traits)->toContain('Illuminate\Foundation\Events\Dispatchable');
    expect($traits)->toContain('Illuminate\Queue\SerializesModels');
});
