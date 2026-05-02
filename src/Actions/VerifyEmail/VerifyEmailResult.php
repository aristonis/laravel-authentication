<?php

declare(strict_types=1);

namespace Aristonis\LaravelAuthentication\Actions\VerifyEmail;

use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Verify Email result - EXTENDABLE by users.
 *
 * Contains verification status and helpful flags.
 *
 * @property bool $success Whether the request was successful
 * @property bool $verified Whether email was just verified (true) or was already verified (false)
 * @property bool $alreadyVerified Whether email was already verified before this request
 * @property string $message User-friendly message
 * @property Authenticatable|null $user The verified user
 *
 * @package Aristonis\LaravelAuthentication\Actions\VerifyEmail
 */
class VerifyEmailResult
{
    /**
     * @param bool $success Whether the request was successful
     * @param bool $verified Whether email was just verified
     * @param bool $alreadyVerified Whether email was already verified
     * @param string $message User-friendly message
     * @param Authenticatable|null $user The user
     */
    public function __construct(
        public readonly bool $success,
        public readonly bool $verified = true,
        public readonly bool $alreadyVerified = false,
        public readonly string $message = 'Email verified successfully',
        public readonly ?Authenticatable $user = null,
    ) {}

    /**
     * Check if email was newly verified.
     */
    public function wasNewlyVerified(): bool
    {
        return $this->verified && !$this->alreadyVerified;
    }

    /**
     * Get all attributes as an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'verified' => $this->verified,
            'already_verified' => $this->alreadyVerified,
            'message' => $this->message,
        ];
    }
}
