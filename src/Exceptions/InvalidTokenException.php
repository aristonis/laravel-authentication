<?php

declare(strict_types=1);

namespace Aristonis\LaravelAuthentication\Exceptions;

class InvalidTokenException extends AuthenticationException
{
    public function __construct(string $message = 'Invalid token')
    {
        parent::__construct($message, 400);
    }
}
