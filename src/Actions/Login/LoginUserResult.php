<?php

declare(strict_types=1);

namespace Aristonis\LaravelAuthentication\Actions\Login;

use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Login result - EXTENDABLE by users.
 *
 * Contains only user and meta - each login type adds its own meta fields.
 *
 * @property Authenticatable $user The authenticated user
 * @property array $meta Additional data (token, session_id, expires_in, etc.)
 *
 * @example
 * // API: meta = ['token' => '...', 'token_type' => 'Bearer']
 * // Web: meta = ['session_id' => '...', 'redirect_url' => '...']
 * // Mobile: meta = ['access_token' => '...', 'expires_in' => 3600]
 */
class LoginUserResult
{
    public function __construct(
        public readonly Authenticatable $user,
        public readonly array $meta = [],
    ) {}
}
