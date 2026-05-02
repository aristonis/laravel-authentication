<?php

declare(strict_types=1);

namespace Aristonis\LaravelAuthentication\Exceptions;

class ValidationException extends AuthenticationException
{
    /**
     * @param array<string> $errors Validation errors
     * @param string $message Error message
     */
    public function __construct(
        public readonly array $errors = [],
        string $message = 'Validation failed',
    ) {
        parent::__construct($message, 422);
    }
}
