<?php

declare(strict_types=1);

namespace Aristonis\LaravelAuthentication\Contracts;

interface ActionInterface
{
    /**
     * Execute the action.
     */
    public function __invoke(object $dto): object;

    /**
     * Get the action's container binding name.
     */
    public static function resolveName(): string;
}
