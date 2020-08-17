<?php

namespace PhpMimeMailParser;

/**
 * Wraps a callable as a Middleware
 */
final class Middleware implements Contracts\Middleware
{
    /**
     * @var callable
     */
    protected $parser;

    /**
     * Create a middleware using a callable $fn
     *
     * @param callable $fn
     */
    public function __construct(callable $fn)
    {
        $this->parser = $fn;
    }

    public function process(Entity $entity, MiddlewareStack $next): Entity
    {
        return call_user_func($this->parser, $entity, $next);
    }
}
