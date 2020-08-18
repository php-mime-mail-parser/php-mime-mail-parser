<?php

namespace PhpMimeMailParser;

use PhpMimeMailParser\Contracts\Middleware;

/**
 * A stack of middleware chained together by (MiddlewareStack $next)
 */
final class MiddlewareStack
{
    /**
     * Next MiddlewareStack in chain
     *
     * @var MiddlewareStack
     */
    protected $next;

    /**
     * Middleware in this MiddlewareStack
     *
     * @var Middleware
     */
    protected $middleware;

    /**
     * Construct the first middleware in this MiddlewareStack
     * The next middleware is chained through $MiddlewareStack->add($Middleware)
     *
     * @param Middleware $middleware
     */
    public function __construct(MiddleWare $middleware = null)
    {
        $this->middleware = $middleware;
    }

    /**
     * Creates a chained middleware in MiddlewareStack
     *
     * @param Middleware $middleware
     * @return MiddlewareStack Immutable MiddlewareStack
     */
    public function add(Middleware $middleware)
    {
        $stack = new static($middleware);
        $stack->next = $this;
        return $stack;
    }

    public function parse(Entity $entity): Entity
    {
        if (empty($this->middleware)) {
            return $entity;
        }
        return call_user_func([$this->middleware, 'process'], $entity, $this->next);
    }

    /**
     * Creates a MiddlewareStack based on an array of middleware
     *
     * @param Middleware[] $middlewares
     */
    public static function factory(array $middlewares = []): \PhpMimeMailParser\MiddlewareStack
    {
        $stack = new static;
        foreach ($middlewares as $middleware) {
            $stack = $stack->add($middleware);
        }
        return $stack;
    }
    
    public function __invoke(Entity $entity): Entity
    {
        return $this->parse($entity);
    }
}
