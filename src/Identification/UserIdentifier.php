<?php

declare(strict_types=1);

namespace Aristonis\LaravelAuthentication\Identification;

use Aristonis\LaravelAuthentication\Contracts\UserIdentifierInterface;
use Illuminate\Config\Repository;

/**
 * Default user identifier implementation.
 *
 * Searches for users across multiple fields using OR logic.
 * Example: WHERE email = ? OR username = ? OR phone = ?
 *
 * Users can extend this class or implement UserIdentifierInterface
 * for custom identification logic (LDAP, OAuth, etc.)
 */
class UserIdentifier implements UserIdentifierInterface
{
    public function __construct(
        protected readonly Repository $config,
    ) {}

    public function findUser(string $identifier): ?\Illuminate\Contracts\Auth\Authenticatable
    {
        $modelClass = $this->getModelClass();
        $fields = $this->getFields();

        // Single query: WHERE email = ? OR username = ? OR phone = ?
        return $modelClass::query()
            ->whereAny($fields, '=', $identifier)
            ->first();
    }

    public function getRateLimitKey(string $identifier): string
    {
        return 'identifier:' . md5(strtolower($identifier));
    }

    /**
     * Get the user model class from config.
     *
     * Override this method to use a different model source.
     */
    protected function getModelClass(): string
    {
        return config('auth.providers.users.model', \App\Models\User::class);
    }

    /**
     * Get the fields to search for identification.
     *
     * Override this method to customize fields dynamically.
     */
    protected function getFields(): array
    {
        return $this->config->get('laravel-authentication.identification.fields', ['email']);
    }
}
