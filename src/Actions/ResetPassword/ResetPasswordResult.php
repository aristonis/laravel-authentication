<?php

declare(strict_types=1);

namespace Aristonis\LaravelAuthentication\Actions\ResetPassword;

use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Reset Password result - EXTENDABLE by users.
 *
 * Contains success status, user, and optional new token (for API).
 *
 * @property bool $success Whether the reset was successful
 * @property Authenticatable $user The user whose password was reset
 * @property string|null $newToken New authentication token (API only)
 * @property string $message User-friendly message
 *
 * @package Aristonis\LaravelAuthentication\Actions\ResetPassword
 */
class ResetPasswordResult
{
    /**
     * @param bool $success Whether the reset was successful
     * @param Authenticatable $user The user
     * @param string|null $newToken New auth token (API only)
     * @param string $message User-friendly message
     */
    public function __construct(
        public readonly bool $success,
        public readonly Authenticatable $user,
        public readonly ?string $newToken = null,
        public readonly string $message = 'Password reset successfully',
    ) {}

    /**
     * Check if new token was returned.
     */
    public function hasNewToken(): bool
    {
        return $this->newToken !== null;
    }

    /**
     * Get all attributes as an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'success' => $this->success,
            'new_token' => $this->newToken,
            'message' => $this->message,
        ], fn ($value) => $value !== null);
    }
}
