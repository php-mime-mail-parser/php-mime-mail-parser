<?php

namespace PhpMimeMailParser;

enum AttachmentFilenameStrategy: string
{
    case DuplicateThrow = Parser::ATTACHMENT_DUPLICATE_THROW;
    case DuplicateSuffix = Parser::ATTACHMENT_DUPLICATE_SUFFIX;
    case RandomFilename = Parser::ATTACHMENT_RANDOM_FILENAME;
}
