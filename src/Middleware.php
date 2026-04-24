<?php

namespace PhpMimeMailParser;

/**
 * Wraps a callable as a Middleware
 */
class Middleware implements Contracts\Middleware
{
    /**
     * Callable middleware function
     */
    private readonly \Closure $parser;

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
    public function parse(MimePart $part, MiddlewareStack $next): MimePart
    {
        return ($this->parser)($part, $next);
    }
}
