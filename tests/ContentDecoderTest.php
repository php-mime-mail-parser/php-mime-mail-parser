<?php
namespace Tests\PhpMimeMailParser;

use PhpMimeMailParser\ContentDecoder;

/**
 * @covers \PhpMimeMailParser\ContentDecoder
 */
final class ContentDecoderTest extends TestCase
{
    public function testDecodeUnknownEncoding()
    {
        $decoder = new ContentDecoder();

        $this->assertSame('testing', $decoder->decodeContentTransfer('testing', 'unknown'));
        $this->assertSame('testing', $decoder->decodeContentTransfer('testing', ''));
    }

    public function testDecodeQuotedPrintable()
    {
        $decoder = new ContentDecoder();
        $decoded = $decoder->decodeContentTransfer(
            '=D0=9F=D1=80=D0=BE=D0=B2=D0=B5=D1=80=D0=BA=D0=B0',
            $decoder::ENCODING_QUOTED_PRINTABLE
        );

        $this->assertSame('Проверка', $decoded);
    }

    public function testDecodeBase64()
    {
        $decoder = new ContentDecoder();
        $decoded = $decoder->decodeContentTransfer('YW55IGNhcm5hbCBwbGVhc3VyZQ==', $decoder::ENCODING_BASE64);

        $this->assertSame('any carnal pleasure', $decoded);
    }

    public function testDecodeCaseInsensitive()
    {
        $decoder = new ContentDecoder();
        $decoded = $decoder->decodeContentTransfer(
            '=D0=9F=D1=80=D0=BE=D0=B2=D0=B5=D1=80=D0=BA=D0=B0',
            strtoupper($decoder::ENCODING_QUOTED_PRINTABLE)
        );

        $this->assertSame('Проверка', $decoded);
    }
}
