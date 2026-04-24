<?php namespace PhpMimeMailParser\Contracts;

interface CharsetManager
{

    /**
     * Decode the string from Charset
     *
     * @param string $encodedString The string in its original encoded state
     * @param string $charset       The Charset header of the part.
     */
    public function decodeCharset(string $encodedString, string $charset): string;

    /**
     * Get charset alias
     *
     * @param string $charset .
     */
    public function getCharsetAlias(string $charset): string;
}
