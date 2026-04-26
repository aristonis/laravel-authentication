<?php

declare(strict_types=1);

namespace Aristonis\LaravelAuthentication\Exceptions;

class UserAlreadyExistsException extends AuthenticationException
{
    public function __construct(string $message = 'User already exists')
    {
        parent::__construct($message, 409);
    }
}
