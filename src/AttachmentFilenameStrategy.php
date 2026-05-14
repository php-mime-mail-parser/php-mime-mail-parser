<?php

namespace PhpMimeMailParser;

enum AttachmentFilenameStrategy: string
{
    case DuplicateThrow = 'DuplicateThrow';
    case DuplicateSuffix = 'DuplicateSuffix';
    case RandomFilename = 'RandomFilename';
}
