<?php

declare(strict_types=1);

namespace Aristonis\LaravelAuthentication\Actions\VerifyEmail;

/**
 * Verify Email Data Transfer Object.
 *
 * Immutable data container for email verification input.
 * All properties are readonly to ensure immutability.
 *
 * @property string|int $userId User ID
 * @property string $token Verification token
 * @property string $email User's email address (for validation)
 *
 * @package Aristonis\LaravelAuthentication\Actions\VerifyEmail
 */
final readonly class VerifyEmailDto
{
    /**
     * @param string|int $userId User ID
     * @param string $token Verification token
     * @param string $email User's email address
     */
    public function __construct(
        public string|int $userId,
        public string $token,
        public string $email,
    ) {}

    /**
     * Get all attributes as an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'token' => $this->token,
            'email' => $this->email,
        ];
    }
}
