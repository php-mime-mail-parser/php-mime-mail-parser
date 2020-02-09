<?php

namespace PhpMimeMailParser;

use PhpMimeMailParser\Contracts\CharsetManager;
use PhpMimeMailParser\Contracts\ContentTransferDecoder;

/**
 * Header decoder decodes MIME-encoded headers.
 */
final class MimeDecoder implements Contracts\HeaderDecoder
{
    /**
     * @var CharsetManager
     */
    private $charset;

    /**
     * @var ContentTransferDecoder
     */
    private $decoder;

    /**
     * Parser constructor.
     *
     * @param CharsetManager|null $charset
     */
    public function __construct(CharsetManager $charset, ContentTransferDecoder $decoder)
    {
        $this->charset = $charset;
        $this->decoder = $decoder;
    }

    /**
     * Decodes a single header
     *
     * @param string $input
     *
     * @return string
     */
    public function decodeHeader(string $input): string
    {
        // For each encoded-word...
        while (preg_match('/(=\?([^?]+)\?(q|b)\?([^?]*)\?=)((\s+)=\?)?/i', $input, $matches)) {
            $encoded = $matches[1];
            $charset = $matches[2];
            $encoding = $matches[3];
            $text = $matches[4];
            $space = isset($matches[6]) ? $matches[6] : '';

            switch (strtolower($encoding)) {
                case 'b':
                    $text = $this->decoder->decodeContentTransfer($text, $this->decoder::ENCODING_BASE64);
                    break;

                case 'q':
                    $text = str_replace('_', ' ', $text);
                    $text = $this->decoder->decodeContentTransfer($text, $this->decoder::ENCODING_QUOTED_PRINTABLE);
                    break;
            }

            $text = $this->charset->decodeCharset($text, $charset);
            $input = str_replace($encoded.$space, $text, $input);
        }

        return $input;
    }
}
