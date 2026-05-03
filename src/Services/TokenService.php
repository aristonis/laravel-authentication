<?php

declare(strict_types=1);

namespace Aristonis\LaravelAuthentication\Services;

use Aristonis\LaravelAuthentication\Contracts\TokenServiceInterface;
use Illuminate\Contracts\Auth\Authenticatable;
use Laravel\Sanctum\PersonalAccessToken;

class TokenService implements TokenServiceInterface
{
    public function createToken(
        Authenticatable $user,
        ?string $name = null,
        ?array $abilities = null,
        ?\DateTimeInterface $expiresAt = null
    ): string {
        $tokenName = $name ?? config('laravel-authentication.sanctum.token_name', 'auth_token');
        
        // Use provided abilities or default from config
        if ($abilities === null) {
            $abilities = config('laravel-authentication.sanctum.abilities', ['*']);
        }
        
        $token = $user->createToken($tokenName, $abilities);
        
        if ($expiresAt) {
            $token->accessToken->expires_at = $expiresAt;
            $token->accessToken->save();
        }
        
        return $token->plainTextToken;
    }

    public function revokeToken(string $token): bool
    {
        $personalToken = PersonalAccessToken::findToken($token);
        
        if ($personalToken) {
            return $personalToken->delete();
        }
        
        return false;
    }

    public function findToken(string $token): ?object
    {
        return PersonalAccessToken::findToken($token);
    }
}
