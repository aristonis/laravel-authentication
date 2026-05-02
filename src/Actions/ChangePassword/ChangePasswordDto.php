<?php

declare(strict_types=1);

namespace Aristonis\LaravelAuthentication\Actions\ChangePassword;

use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Change Password Data Transfer Object.
 *
 * Immutable data container for password change input.
 * All properties are readonly to ensure immutability.
 *
 * @property Authenticatable $user The user changing password
 * @property string $oldPassword Current password
 * @property string $newPassword New password
 *
 * @package Aristonis\LaravelAuthentication\Actions\ChangePassword
 */
final readonly class ChangePasswordDto
{
    /**
     * @param Authenticatable $user The user changing password
     * @param string $oldPassword Current password
     * @param string $newPassword New password
     */
    public function __construct(
        public Authenticatable $user,
        public string $oldPassword,
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
            'user_id' => $this->user->getAuthIdentifier(),
            'old_password' => '***', // Never return actual password
            'new_password' => '***', // Never return actual password
        ];
    }
}
