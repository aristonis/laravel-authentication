<?php

declare(strict_types=1);

namespace Aristonis\LaravelAuthentication\Actions\Register;

/**
 * Registration Data Transfer Object.
 *
 * Immutable data container for user registration input.
 * All properties are readonly to ensure immutability.
 *
 * @property string $email User's email address
 * @property string $password User's password (plain text, will be hashed)
 * @property string|null $name User's name (optional)
 * @property string|null $ipAddress Client IP address (for rate limiting/auditing)
 * @property array<string, mixed> $additional Additional attributes for custom fields
 *
 * @package Aristonis\LaravelAuthentication\Actions\Register
 */
final readonly class RegisterUserDto
{
    /**
     * @param string $email User's email address
     * @param string $password User's password (plain text)
     * @param string|null $name User's name (optional)
     * @param string|null $ipAddress Client IP address (optional, for rate limiting)
     * @param array<string, mixed> $additional Additional attributes (optional, for custom fields)
     */
    public function __construct(
        public string $email,
        public string $password,
        public ?string $name = null,
        public ?string $ipAddress = null,
        public array $additional = [],
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
            'password' => $this->password,
            'name' => $this->name,
            'ip_address' => $this->ipAddress,
            ...$this->additional,
        ], fn ($value) => $value !== null);
    }
}
