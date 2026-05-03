<?php

declare(strict_types=1);

namespace Aristonis\LaravelAuthentication\Contracts;

/**
 * Interface for all authentication actions.
 *
 * @template TDto of object The input DTO type
 * @template TResult of object The result type
 */
interface ActionInterface
{
    /**
     * Execute the action.
     *
     * @param TDto $dto The input DTO
     * @return TResult The action result
     */
    public function __invoke(object $dto): object;

    /**
     * Get the action's container binding name.
     */
    public static function resolveName(): string;
}
