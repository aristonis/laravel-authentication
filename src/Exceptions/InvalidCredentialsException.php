<?php

declare(strict_types=1);

namespace Aristonis\LaravelAuthentication\Exceptions;

class InvalidCredentialsException extends AuthenticationException
{
    public function __construct(string $message = 'Invalid credentials')
    {
        parent::__construct($message, 401);
    }
}
