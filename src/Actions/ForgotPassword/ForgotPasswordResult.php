<?php

declare(strict_types=1);

namespace Aristonis\LaravelAuthentication\Actions\ForgotPassword;

use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Forgot Password result - EXTENDABLE by users.
 *
 * Contains success status, optional reset token (for API), and message.
 *
 * @property bool $success Whether the request was successful
 * @property string|null $resetToken Reset token (API only, null for web/mobile)
 * @property string $message User-friendly message
 * @property Authenticatable|null $user The user who requested reset (if found)
 *
 * @package Aristonis\LaravelAuthentication\Actions\ForgotPassword
 */
class ForgotPasswordResult
{
    /**
     * @param bool $success Whether the request was successful
     * @param string|null $resetToken Reset token (API only)
     * @param string $message User-friendly message
     * @param Authenticatable|null $user The user (if found)
     */
    public function __construct(
        public readonly bool $success,
        public readonly ?string $resetToken = null,
        public readonly string $message = 'Password reset link sent',
        public readonly ?Authenticatable $user = null,
    ) {}

    /**
     * Check if reset token was returned.
     */
    public function hasResetToken(): bool
    {
        return $this->resetToken !== null;
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
            'reset_token' => $this->resetToken,
            'message' => $this->message,
        ], fn ($value) => $value !== null);
    }
}
