<?php namespace PhpMimeMailParser\Contracts;

interface CharsetManager
{

    /**
     * Decode the string from Charset
     */
    public function decodeCharset(string $encodedString, string $charset): string;

    /**
     * Get charset alias
     */
    public function getCharsetAlias(string $charset): string;
}
