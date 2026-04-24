<?php

namespace PhpMimeMailParser;

/**
 * Wraps a callable as a Middleware
 *
 * This class provides a way to wrap callables (including first-class callables in PHP 8.1+)
 * as middleware for processing MIME parts.
 *
 * @since 8.0 - Supports first-class callables
 * @since 8.1 - Recommended pattern: new Middleware(ParserClass::parseMethod(...))
 */
class Middleware implements Contracts\Middleware
{
    /**
     * Callable middleware function (can be first-class callable in PHP 8.1+)
     */
    private readonly \Closure $parser;

    /**
     * Create a middleware using a callable
     *
     * Supports:
     * - Closures: fn() => ...
     * - Functions: 'function_name'
     * - Methods: [$object, 'method'] or 'ClassName::method'
     * - First-class callables (PHP 8.1+): ClassName::method(...)
     *
     * @param callable $fn The callable to wrap as middleware
     */
    public function __construct(callable $fn)
    {
        // Converts any callable to a Closure for consistent handling
        $this->parser = \Closure::fromCallable($fn);
    }

    /**
     * Process a mime part, optionally delegating parsing to the $next MiddlewareStack
     *
     * @param MimePart $part The MIME part to process
     * @param MiddlewareStack $next The next middleware in the stack
     * @return MimePart The processed MIME part
     */
    public function parse(MimePart $part, MiddlewareStack $next): MimePart
    {
        return ($this->parser)($part, $next);
    }
}
