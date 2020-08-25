<?php

namespace PhpMimeMailParser;

use PhpMimeMailParser\Contracts\ContentTransferEncodingManager;

/**
 * Content transfer decoder of php-mime-mail-parser
 * @see \Tests\PhpMimeMailParser\ContentTransferDecoderTest
 */
final class ContentTransferDecoder implements ContentTransferEncodingManager
{
    public function decodeContentTransfer(string $encodedString, string $encodingType): string
    {
        $encodingType = trim(strtolower($encodingType));

        if (self::ENCODING_BASE64 === $encodingType) {
            return base64_decode($encodedString);
        }

        if (self::ENCODING_QUOTED_PRINTABLE === $encodingType) {
            return quoted_printable_decode($encodedString);
        }

        if (self::ENCODING_UUENCODE === $encodingType) {
            if (strpos($encodedString, "begin") === 0 && strpos($encodedString, "begin") < strpos($encodedString, PHP_EOL)) {
                $encodedString = substr($encodedString, strpos($encodedString, PHP_EOL) + 1);
            }
            $encodedString = substr($encodedString, 0, strripos($encodedString, "end") - 1);
            return convert_uudecode($encodedString);
        }

        return $encodedString;
    }
}
