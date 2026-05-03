<?php

declare(strict_types=1);

namespace Aristonis\LaravelAuthentication\Actions\ForgotPassword\Events;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event dispatched when a password reset link is sent.
 *
 * Listeners can be configured in config:
 * - laravel-authentication.events.password_reset_link_sent
 *
 * Use Cases:
 * - Send password reset email
 * - Log password reset request for security auditing
 * - Notify user via SMS (mobile)
 *
 * @package Aristonis\LaravelAuthentication\Actions\ForgotPassword\Events
 */
class PasswordResetLinkSentEvent
{
    use Dispatchable, SerializesModels;

    /**
     * @param Authenticatable $user The user who requested reset
     * @param string $channel The channel used (email, sms)
     * @param string|null $resetToken The reset token (if applicable)
     */
    public function __construct(
        public readonly Authenticatable $user,
        public readonly string $channel = 'email',
        public readonly ?string $resetToken = null,
    ) {}
}
