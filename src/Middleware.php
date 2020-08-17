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

    /**
     * Process a mime part, optionally delegating parsing to the $next MiddlewareStack
     */
    public function parse(Entity $part, MiddlewareStack $next): Entity
    {
        return call_user_func($this->parser, $part, $next);
    }
}
