<?php
namespace Tests\PhpMimeMailParser;

use PhpMimeMailParser\ContentTransferDecoder;

/**
 * @covers \PhpMimeMailParser\ContentTransferDecoder
 */
final class ContentTransferDecoderTest extends TestCase
{
    public function testDecodeUnknownEncoding(): void
    {
        $decoder = new ContentTransferDecoder();

        $this->assertSame('testing', $decoder->decodeContentTransfer('testing', 'unknown'));
        $this->assertSame('testing', $decoder->decodeContentTransfer('testing', ''));
    }

    public function testDecodeQuotedPrintable(): void
    {
        $decoder = new ContentTransferDecoder();
        $decoded = $decoder->decodeContentTransfer(
            '=D0=9F=D1=80=D0=BE=D0=B2=D0=B5=D1=80=D0=BA=D0=B0',
            $decoder::ENCODING_QUOTED_PRINTABLE
        );

        $this->assertSame('Проверка', $decoded);
    }

    public function testDecodeBase64(): void
    {
        $decoder = new ContentTransferDecoder();
        $decoded = $decoder->decodeContentTransfer('YW55IGNhcm5hbCBwbGVhc3VyZQ==', $decoder::ENCODING_BASE64);

        $this->assertSame('any carnal pleasure', $decoded);
    }

    public function testDecodeCaseInsensitive(): void
    {
        $decoder = new ContentTransferDecoder();
        $decoded = $decoder->decodeContentTransfer(
            '=D0=9F=D1=80=D0=BE=D0=B2=D0=B5=D1=80=D0=BA=D0=B0',
            strtoupper($decoder::ENCODING_QUOTED_PRINTABLE)
        );

        $this->assertSame('Проверка', $decoded);
    }
}
