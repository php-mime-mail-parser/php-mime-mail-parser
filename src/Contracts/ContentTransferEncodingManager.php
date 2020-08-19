<?php

namespace PhpMimeMailParser\Contracts;

interface ContentTransferEncodingManager
{
    /**
     * @var string
     */
    public const ENCODING_BASE64 = 'base64';

    /**
     * @var string
     */
    public const ENCODING_QUOTED_PRINTABLE = 'quoted-printable';

    /**
     * @var string
     */
    public const ENCODING_UUENCODE = 'uuencode';

    /**
     * Decode the string from Content-Transfer-Encoding
     *
     * @param string $encodedString The string in its original encoded state
     * @param string $encodingType  The encoding type from the Content-Transfer-Encoding header of the part.
     *
     * @return string The decoded string
     */
    public function decodeContentTransfer(string $encodedString, string $encodingType): string;
}
