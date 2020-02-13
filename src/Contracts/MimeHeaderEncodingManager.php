<?php

namespace PhpMimeMailParser\Contracts;

interface MimeHeaderEncodingManager
{
    /**
     * Decodes a single MIME-encoded header.
     *
     * @param string $input
     *
     * @return string
     */
    public function decodeHeader(string $input): string;
}
