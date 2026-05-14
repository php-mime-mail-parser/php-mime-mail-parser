<?php

namespace PhpMimeMailParser;

enum MessageBodyType: string
{
    case Text = 'text';
    case Html = 'html';
    case HtmlEmbedded = 'htmlEmbedded';
}
