<?php

declare(strict_types=1);

namespace Aristonis\LaravelAuthentication\Actions\Logout\Events;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event dispatched when a user logs out.
 *
 * Listeners can be configured in config:
 * - auth-package.events.user_logged_out
 *
 * Use Cases:
 * - Log logout for security auditing
 * - Clear user-specific caches
 * - Notify other devices of logout
 * - Track session analytics
 *
 * @package Aristonis\LaravelAuthentication\Actions\Logout\Events
 */
class UserLoggedOutEvent
{
    use Dispatchable, SerializesModels;

    /**
     * @param Authenticatable $user The user who logged out
     */
    public function __construct(
        public readonly Authenticatable $user,
    ) {}
}
