<?php

declare(strict_types=1);

namespace Aristonis\LaravelAuthentication\Actions\ResetPassword\Events;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event dispatched when a user successfully resets their password.
 *
 * Listeners can be configured in config:
 * - auth-package.events.password_reset
 *
 * Use Cases:
 * - Send password changed confirmation email
 * - Log password change for security auditing
 * - Invalidate all other sessions
 * - Notify user of password change
 *
 * @package Aristonis\LaravelAuthentication\Actions\ResetPassword\Events
 */
class PasswordResetEvent
{
    use Dispatchable, SerializesModels;

    /**
     * @param Authenticatable $user The user who reset their password
     */
    public function __construct(
        public readonly Authenticatable $user,
    ) {}
}
