<?php

declare(strict_types=1);

namespace PhpMimeMailParser\Enum;

enum BodyType: string
{
    case Text = 'text';
    case Html = 'html';
}
