<?php

namespace PhpMimeMailParser\Contracts;

use PhpMimeMailParser\MimePart;
use PhpMimeMailParser\MiddlewareStack;

/**
 * Process Mime parts by either:
 *  processing the part or calling the $next MiddlewareStack
 */
interface Middleware
{
    /**
     *  Process a mime part, optionally delegating parsing to the $next MiddlewareStack
     */
    public function parse(MimePart $part, MiddlewareStack $next): MimePart;
}
