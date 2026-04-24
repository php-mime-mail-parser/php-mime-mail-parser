<?php

namespace PhpMimeMailParser;

/**
 * Attachment filename strategy for handling duplicate filenames
 *
 * This enum provides type-safe strategies for saving attachments.
 * It can be used directly or converted from the legacy string constants.
 *
 * @since 8.0
 */
enum AttachmentFilenameStrategy: string
{
    /**
     * Throw an exception if a duplicate filename is encountered
     */
    case DuplicateThrow = 'DuplicateThrow';

    /**
     * Append a numeric suffix to duplicate filenames
     */
    case DuplicateSuffix = 'DuplicateSuffix';

    /**
     * Generate a random filename to avoid duplicates
     */
    case RandomFilename = 'RandomFilename';

    /**
     * Create an enum from a legacy string constant
     * Provides backward compatibility with older code using string constants
     *
     * @param string $value The legacy string value
     * @return self
     * @throws \ValueError If the value doesn't match any strategy
     */
    public static function fromLegacy(string $value): self
    {
        return self::from($value);
    }

    /**
     * Get the string value (legacy compatibility)
     */
    public function toLegacy(): string
    {
        return $this->value;
    }
}
