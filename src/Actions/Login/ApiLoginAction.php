<?php

declare(strict_types=1);

namespace Aristonis\LaravelAuthentication\Actions\Login;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * API login action - creates Sanctum token.
 */
class ApiLoginAction extends AbstractLoginAction
{
    protected function authenticate(
        Authenticatable $user,
        LoginUserDto $dto
    ): LoginUserResult {
        // Create Sanctum token
        $token = $this->tokenService->createToken(
            $user,
            config('auth-package.sanctum.token_name'),
            config('auth-package.sanctum.abilities')
        );

        return new LoginUserResult(
            user: $user,
            meta: [
                'token' => $token,
                'token_type' => 'Bearer',
            ]
        );
    }
}
