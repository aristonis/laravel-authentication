<?php

declare(strict_types=1);

namespace Aristonis\LaravelAuthentication\Actions\Login;

/**
 * Login Data Transfer Object.
 *
 * @property string $identifier User identifier (email, username, phone, etc.)
 * @property string $password User password
 * @property string|null $ipAddress Client IP address
 */
final readonly class LoginUserDto
{
    public function __construct(
        public string $identifier,
        public string $password,
        public ?string $ipAddress = null,
    ) {}
}
