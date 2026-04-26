<?php

declare(strict_types=1);

namespace Aristonis\LaravelAuthentication\Identification;

use Aristonis\LaravelAuthentication\Contracts\UserIdentifierInterface;
use Illuminate\Config\Repository;

/**
 * Factory for creating user identifier instances.
 *
 * Creates the default UserIdentifier or a custom class from config.
 */
class IdentifierFactory
{
    public function __construct(
        protected readonly Repository $config,
    ) {}

    /**
     * Create a user identifier instance.
     */
    public function create(): UserIdentifierInterface
    {
        // Check for custom identifier class in config
        $customClass = $this->config->get('auth-package.identification.custom');

        if ($customClass !== null) {
            return app($customClass);
        }

        // Default: use the built-in UserIdentifier
        return new UserIdentifier($this->config);
    }
}
