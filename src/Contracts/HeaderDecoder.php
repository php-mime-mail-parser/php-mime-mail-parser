<?php

namespace PhpMimeMailParser\Contracts;

interface HeaderDecoder
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
