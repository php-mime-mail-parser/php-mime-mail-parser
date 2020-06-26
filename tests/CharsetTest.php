<?php
namespace Tests\PhpMimeMailParser;

use PhpMimeMailParser\Charset;
use PhpMimeMailParser\ContentTransferDecoder;

/**
 * @covers \PhpMimeMailParser\Charset
 */
final class CharsetTest extends TestCase
{
    /**
     * Follows MIME-encoded header order, for the ease of adding new test cases.
     *
     * @var string[][]
     */
    const DATA_QUOTED_PRINTABLE = [
        ['iso-8859-1', 'HasenundFr=F6sche=2Etxt', 'HasenundFrösche.txt'],
        ['windows-1250', 'Automatyczna odpowied=9F: Piotrze, test z 6 miesi=EAcy',
                         'Automatyczna odpowiedź: Piotrze, test z 6 miesięcy'],
        ['us-ascii', 'Testing', 'Testing'],
        ['utf-8', 'Testing', 'Testing'],
    ];

    /**
     * Follows MIME-encoded header order, for the ease of adding new test cases.
     *
     * @var string[][]
     */
    const DATA_BASE64 = [
        ['UTF-8', '0LPQuNC90LAiIg==', 'гина""'],
        ['iso-2022-jp', 'GyRCJygnJS1iGyhCNDEgGyRCJ2AnZBsoQiAyOC4wOS4yMDE2', 'ЖД№41 от 28.09.2016'],
        ['ISO-2022-JP-MS', 'GyRCLSEtIi1qfGIbKEkxMjM0NRsoQg==', '①②㈱髙ｱｲｳｴｵ'],
        ['iso-8859-8-i', '7vLw5CAn4PDpIOzgIPDu9uAnOiBJbnZvaWNlIDAyNzIyMDI3', "מענה 'אני לא נמצא': Invoice 02722027"],
        ['ISO-8859-1', 'SWYgeW91IGNhbiByZWFkIHRoaXMgeW8', "If you can read this yo"],
    ];

    /**
     * @dataProvider provideData
     */
    public function testDecode($charset, $input, $expected): void
    {
        $decoder = new Charset();
        $actual = $decoder->decodeCharset($input, $charset);

        $this->assertSame($expected, $actual);
    }

    /**
     * @return string[][]
     */
    public function provideData(): iterable
    {
        $ctDecoder = new ContentTransferDecoder();

        foreach (self::DATA_QUOTED_PRINTABLE as $row) {
            $row[1] = $ctDecoder->decodeContentTransfer($row[1], $ctDecoder::ENCODING_QUOTED_PRINTABLE);

            yield $row;
        }

        foreach (self::DATA_BASE64 as $row) {
            $row[1] = $ctDecoder->decodeContentTransfer($row[1], $ctDecoder::ENCODING_BASE64);

            yield $row;
        }
    }

    public function testNoErrorOnUnknownEncoding(): void
    {
        $decoder = new Charset();
        $actual = $decoder->decodeCharset('unintelligible', 'plain wrong');

        $this->assertSame('unintelligible', $actual);
    }

    public function testGetCharsetAlias(): void
    {
        $decoder = new Charset();

        $this->assertSame('us-ascii', $decoder->getCharsetAlias('ascii'));

        $this->assertSame('iso-2022-jp', $decoder->getCharsetAlias('iso-2022-jp-ms'));
    }

    /**
     * @requires extension mbstring
     */
    public function testSupportedEncodingsCache(): void
    {
        $decoder = new Charset();

        $expected = $decoder->decodeCharset('Test', 'ISO-8859-1');
        $actual = $decoder->decodeCharset('Test', 'ISO-8859-1');

        $this->assertSame($expected, $actual);
    }

    /**
     * @requires extension mbstring
     */
    public function testSupportedEncodings(): void
    {
        $decoder = new Charset();

        $supportedEncodings = (function (): array {
            return $this->getSupportedEncodings();
        })->call($decoder);
        sort($supportedEncodings);

        $legacySupportedEncodings = $this->getSupportedEncodingsLegacy();
        sort($legacySupportedEncodings);

        $this->assertEquals($legacySupportedEncodings, $supportedEncodings);
    }

    /**
     * @see Charset::getSupportedEncodings()
     * @return string[]
     */
    private function getSupportedEncodingsLegacy(): array
    {
        return
        array_map(
            'strtolower',
            array_unique(
                array_merge(
                    $enc = mb_list_encodings(),
                    array_merge(...array_map(
                        "mb_encoding_aliases",
                        $enc
                    ))
                )
            )
        );
    }
}
