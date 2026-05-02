<?php

declare(strict_types=1);

namespace Aristonis\LaravelAuthentication\Actions\Logout;

/**
 * Logout result - EXTENDABLE by users.
 *
 * Contains success status and message.
 *
 * @property bool $success Whether the logout was successful
 * @property string $message User-friendly message
 *
 * @package Aristonis\LaravelAuthentication\Actions\Logout
 */
class LogoutResult
{
    /**
     * @param bool $success Whether the logout was successful
     * @param string $message User-friendly message
     */
    public function __construct(
        public readonly bool $success,
        public readonly string $message = 'Logged out successfully',
    ) {}

    /**
     * Get all attributes as an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'message' => $this->message,
        ];
    }
}
