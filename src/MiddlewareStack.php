<?php

namespace PhpMimeMailParser;

use PhpMimeMailParser\Contracts\Middleware as MiddlewareContract;

/**
 * A stack of middleware chained together by (MiddlewareStack $next)
 */
class MiddlewareStack
{
    protected ?MiddlewareStack $next = null;

    protected ?MiddlewareContract $middleware = null;

    /**
     * Construct the first middleware in this MiddlewareStack
     * The next middleware is chained through $MiddlewareStack->add($Middleware)
     */
    public function __construct(?MiddlewareContract $middleware = null)
    {
        $this->middleware = $middleware;
    }

    /**
     * Creates a chained middleware in MiddlewareStack
     */
    public function add(MiddlewareContract $middleware): self
    {
        $stack = new self($middleware);
        $stack->next = $this;
        return $stack;
    }

    /**
     * Parses the MimePart by passing it through the Middleware
     */
    public function parse(MimePart $part): MimePart
    {
        if (!$this->middleware) {
            return $part;
        }
        $part = $this->middleware->parse($part, $this->next);
        return $part;
    }

    /**
     * Creates a MiddlewareStack based on an array of middleware
     *
     * @param list<MiddlewareContract> $middlewares
     */
    public static function factory(array $middlewares = []): self
    {
        $stack = new self;
        foreach ($middlewares as $middleware) {
            $stack = $stack->add($middleware);
        }
        return $stack;
    }

    /**
     * Allow calling MiddlewareStack instance directly to invoke parse()
     */
    public function __invoke(MimePart $part)
    {
        return $this->parse($part);
    }
}
