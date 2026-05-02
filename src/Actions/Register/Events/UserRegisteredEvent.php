<?php

declare(strict_types=1);

namespace Aristonis\LaravelAuthentication\Actions\Register\Events;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event dispatched when a user successfully registers.
 *
 * Listeners can be configured in config:
 * - auth-package.events.user_registered
 *
 * Use Cases:
 * - Send welcome email
 * - Track registration analytics
 * - Provision user resources
 * - Notify admin of new registration
 *
 * @package Aristonis\LaravelAuthentication\Actions\Register\Events
 */
class UserRegisteredEvent
{
    use Dispatchable, SerializesModels;

    /**
     * @param Authenticatable $user The newly registered user
     * @param bool $autoLoggedIn Whether the user was automatically logged in
     */
    public function __construct(
        public readonly Authenticatable $user,
        public readonly bool $autoLoggedIn = false,
    ) {}
}
