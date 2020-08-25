<?php

namespace PhpMimeMailParser;

use PhpMimeMailParser\Contracts\CharsetManager;
use PhpMimeMailParser\Contracts\ContentTransferEncodingManager;

/**
 * Header decoder decodes MIME-encoded headers.
 * @see \Tests\PhpMimeMailParser\MimeHeaderDecoderTest
 */
final class MimeHeaderDecoder implements Contracts\MimeHeaderEncodingManager
{
    /**
     * @var CharsetManager|Charset
     */
    private $charset;

    /**
     * @var ContentTransferEncodingManager|ContentTransferDecoder
     */
    private $decoder;

    public function __construct(CharsetManager $charset, ContentTransferEncodingManager $decoder)
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
        // This function will emit a warning on a broken example,
        // but we'll just continue with out alternative approach
        $result = @iconv_mime_decode($input);

        if (false !== $result) {
            return $result;
        }

        // For each encoded-word...
        while (preg_match('#(=\?([^?]+)\?(q|b)\?([^?]*)\?=)((\s+)=\?)?#i', $input, $matches)) {
            $encoded = $matches[1];
            $charset = $matches[2];
            $encoding = $matches[3];
            $text = $matches[4];
            $space = isset($matches[6]) ? $matches[6] : '';

            switch ($encoding) {
                case 'B':
                case 'b':
                    $text = $this->decoder->decodeContentTransfer($text, $this->decoder::ENCODING_BASE64);
                    break;

                case 'Q':
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
