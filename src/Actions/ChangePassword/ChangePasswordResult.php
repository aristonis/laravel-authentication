<?php

declare(strict_types=1);

namespace Aristonis\LaravelAuthentication\Actions\ChangePassword;

use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Change Password result - EXTENDABLE by users.
 *
 * Contains success status and optional new token.
 *
 * @property bool $success Whether the password change was successful
 * @property Authenticatable $user The user whose password was changed
 * @property string|null $newToken New authentication token (if regenerated)
 * @property string $message User-friendly message
 *
 * @package Aristonis\LaravelAuthentication\Actions\ChangePassword
 */
class ChangePasswordResult
{
    /**
     * @param bool $success Whether the password change was successful
     * @param Authenticatable $user The user
     * @param string|null $newToken New auth token (if regenerated)
     * @param string $message User-friendly message
     */
    public function __construct(
        public readonly bool $success,
        public readonly Authenticatable $user,
        public readonly ?string $newToken = null,
        public readonly string $message = 'Password changed successfully',
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
