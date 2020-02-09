<?php namespace PhpMimeMailParser\Contracts;

interface CharsetManager
{
    /**
     * Decode the string from Charset
     *
     * @param string $encodedString The string in its original encoded state
     * @param string $charset       The Charset header of the part.
     *
     * @return string The decoded string
     */
    public function decodeCharset(string $encodedString, string $charset): string;

    /**
     * Get charset alias
     *
     * @param string $charset .
     *
     * @return string The charset alias
     */
    public function getCharsetAlias(string $charset): string;
}
