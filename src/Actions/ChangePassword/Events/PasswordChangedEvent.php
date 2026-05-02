<?php

declare(strict_types=1);

namespace Aristonis\LaravelAuthentication\Actions\ChangePassword\Events;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event dispatched when a user successfully changes their password.
 *
 * Listeners can be configured in config:
 * - auth-package.events.password_changed
 *
 * Use Cases:
 * - Send password changed confirmation email
 * - Log password change for security auditing
 * - Invalidate other sessions (optional)
 * - Notify user of password change
 *
 * @package Aristonis\LaravelAuthentication\Actions\ChangePassword\Events
 */
class PasswordChangedEvent
{
    use Dispatchable, SerializesModels;

    /**
     * @param Authenticatable $user The user who changed their password
     */
    public function __construct(
        public readonly Authenticatable $user,
    ) {}
}
