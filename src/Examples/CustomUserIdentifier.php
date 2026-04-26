<?php

namespace Aristonis\LaravelAuthentication\Examples;

use Aristonis\LaravelAuthentication\Contracts\UserIdentifierInterface;
use Aristonis\LaravelAuthentication\Identification\UserIdentifier;

/**
 * ============================================================================
 * EXAMPLE: Custom User Identifier
 * ============================================================================
 *
 * This file shows how to extend the authentication package's user
 * identification system.
 *
 * The package uses a flexible identifier system that allows you to:
 * 1. Search across multiple fields (email, username, phone, etc.)
 * 2. Use OR logic automatically
 * 3. Provide completely custom identification logic (LDAP, OAuth, etc.)
 */

// ============================================================================
// OPTION 1: Config-Based (Simplest)
// ============================================================================
//
// In your config/auth-package.php:
//
// 'identification' => [
//     'fields' => ['email', 'username', 'phone'],
//     'custom' => null,
// ],
//
// This automatically searches: WHERE email = ? OR username = ? OR phone = ?
//
// ============================================================================


// ============================================================================
// OPTION 2: Extend Default Class (Medium)
// ============================================================================
//
// Create a class that extends the default UserIdentifier:

class CustomUserIdentifier extends UserIdentifier implements UserIdentifierInterface
{
    /**
     * Find user by identifier.
     *
     * This example allows login with email, username, OR phone number.
     */
    public function findUser(string $identifier): ?\Illuminate\Contracts\Auth\Authenticatable
    {
        $modelClass = $this->getModelClass();

        // You can override getFields() or define custom logic here
        return $modelClass::query()
            ->where('email', $identifier)
            ->orWhere('username', $identifier)
            ->orWhere('phone', $identifier)
            ->orWhere('ldap_id', $identifier)  // Custom field
            ->first();
    }

    /**
     * Customize rate limit key.
     *
     * This is useful if you want different rate limiting for different
     * identifier types.
     */
    public function getRateLimitKey(string $identifier): string
    {
        // Different rate limit keys based on identifier type
        if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
            return 'email:' . md5($identifier);
        }

        if (preg_match('/^\+?[0-9]{10,15}$/', $identifier)) {
            return 'phone:' . md5($identifier);
        }

        return 'username:' . md5($identifier);
    }

    /**
     * Override to customize fields dynamically.
     */
    protected function getFields(): array
    {
        // You could fetch this from database, cache, etc.
        return ['email', 'username', 'phone', 'ldap_id'];
    }
}

// Register in your AppServiceProvider:
//
// public function register(): void
// {
//     $this->app->bind(
//         \Aristonis\LaravelAuthentication\Contracts\UserIdentifierInterface::class,
//         \App\Identifier\CustomUserIdentifier::class
//     );
// }
//
// Or in config/auth-package.php:
//
// 'identification' => [
//     'fields' => ['email'],  // Ignored when custom is set
//     'custom' => \App\Identifier\CustomUserIdentifier::class,
// ],


// ============================================================================
// OPTION 3: Implement Interface (Full Control)
// ============================================================================
//
// For completely custom logic (LDAP, Active Directory, OAuth, etc.):

class LdapUserIdentifier implements UserIdentifierInterface
{
    public function findUser(string $identifier): ?\Illuminate\Contracts\Auth\Authenticatable
    {
        // Query LDAP directory
        $ldapUser = $this->queryLdap($identifier);

        if (!$ldapUser) {
            return null;
        }

        // Sync LDAP user to local database
        $modelClass = config('auth.providers.users.model');

        return $modelClass::updateOrCreate(
            ['ldap_id' => $ldapUser->id],
            [
                'email' => $ldapUser->email,
                'name' => $ldapUser->name,
                'username' => $ldapUser->username,
            ]
        );
    }

    public function getRateLimitKey(string $identifier): string
    {
        return 'ldap:' . md5($identifier);
    }

    private function queryLdap(string $identifier): ?object
    {
        // Your LDAP query logic here
        // This is just an example
        return null;
    }
}


// ============================================================================
// OPTION 4: OAuth/External Provider Identifier
// ============================================================================

class OAuthUserIdentifier implements UserIdentifierInterface
{
    public function findUser(string $oauthId): ?\Illuminate\Contracts\Auth\Authenticatable
    {
        // Find user by OAuth provider ID
        $modelClass = config('auth.providers.users.model');

        return $modelClass::whereHas('oauthProviders', function ($query) use ($oauthId) {
            $query->where('provider_id', $oauthId);
        })->first();
    }

    public function getRateLimitKey(string $oauthId): string
    {
        return 'oauth:' . md5($oauthId);
    }
}


// ============================================================================
// USAGE IN CONTROLLER
// ============================================================================
//
// Your controller code stays the SAME regardless of identifier type:
//
// class AuthController extends Controller
// {
//     public function login(Request $request, LoginUserAction $action)
//     {
//         $request->validate([
//             'identifier' => ['required', 'string'],
//             'password' => ['required', 'min:8'],
//         ]);
//
//         $dto = new LoginUserDto(
//             identifier: $request->input('identifier'),  // Could be email, username, phone
//             password: $request->input('password'),
//             ipAddress: $request->ip(),
//         );
//
//         $result = $action($dto);
//
//         return response()->json($result->toArray());
//     }
// }
//
// The identifier automatically uses YOUR configured identification logic!
