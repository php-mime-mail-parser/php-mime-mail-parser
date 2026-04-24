<?php

namespace PhpMimeMailParser;

class Middleware implements Contracts\Middleware
{
    private readonly \Closure $parser;

    public function __construct(callable $fn)
    {
        $this->parser = \Closure::fromCallable($fn);
    }

    public function parse(MimePart $part, MiddlewareStack $next): MimePart
    {
        return ($this->parser)($part, $next);
    }
}
