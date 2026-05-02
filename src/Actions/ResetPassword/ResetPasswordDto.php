<?php

declare(strict_types=1);

namespace Aristonis\LaravelAuthentication\Actions\ResetPassword;

use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Reset Password Data Transfer Object.
 *
 * Immutable data container for password reset input.
 * All properties are readonly to ensure immutability.
 *
 * @property string $token Password reset token
 * @property string $email User's email address
 * @property string $newPassword New password
 *
 * @package Aristonis\LaravelAuthentication\Actions\ResetPassword
 */
final readonly class ResetPasswordDto
{
    /**
     * @param string $token Password reset token
     * @param string $email User's email address
     * @param string $newPassword New password
     */
    public function __construct(
        public string $token,
        public string $email,
        public string $newPassword,
    ) {}

    /**
     * Get all attributes as an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'token' => $this->token,
            'email' => $this->email,
            'new_password' => $this->newPassword,
        ];
    }
}
