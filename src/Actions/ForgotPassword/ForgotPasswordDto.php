<?php

declare(strict_types=1);

namespace Aristonis\LaravelAuthentication\Actions\ForgotPassword;

/**
 * Forgot Password Data Transfer Object.
 *
 * Immutable data container for forgot password input.
 * All properties are readonly to ensure immutability.
 *
 * @property string $email User's email address
 * @property string|null $ipAddress Client IP address (for rate limiting/auditing)
 */
final readonly class ForgotPasswordDto
{
    /**
     * @param string $email User's email address
     * @param string|null $ipAddress Client IP address (optional, for rate limiting)
     */
    public function __construct(
        public string $email,
        public ?string $ipAddress = null,
    ) {}

    /**
     * Get all attributes as an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'email' => $this->email,
            'ip_address' => $this->ipAddress,
        ], fn ($value) => $value !== null);
    }
}
