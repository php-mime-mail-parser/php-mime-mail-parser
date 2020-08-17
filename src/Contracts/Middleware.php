<?php

namespace PhpMimeMailParser\Contracts;

use PhpMimeMailParser\Entity;
use PhpMimeMailParser\MiddlewareStack;

/**
 * Process Mime parts by either:
 *  processing the part or calling the $next MiddlewareStack
 */
interface Middleware
{
    public function process(Entity $entity, MiddlewareStack $next): Entity;
}
