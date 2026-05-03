<?php

declare(strict_types=1);

namespace Aristonis\LaravelAuthentication\Actions\VerifyEmail\Events;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event dispatched when a user successfully verifies their email.
 *
 * Listeners can be configured in config:
 * - laravel-authentication.events.email_verified
 *
 * Use Cases:
 * - Send welcome email
 * - Grant access to verified-only features
 * - Track verification analytics
 * - Update user status
 *
 * @package Aristonis\LaravelAuthentication\Actions\VerifyEmail\Events
 */
class EmailVerifiedEvent
{
    use Dispatchable, SerializesModels;

    /**
     * @param Authenticatable $user The user who verified their email
     */
    public function __construct(
        public readonly Authenticatable $user,
    ) {}
}
