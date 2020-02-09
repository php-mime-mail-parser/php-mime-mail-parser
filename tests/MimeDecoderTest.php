<?php
namespace Tests\PhpMimeMailParser;

use PhpMimeMailParser\ContentDecoder;
use PhpMimeMailParser\MimeDecoder;
use PhpMimeMailParser\Charset;

/**
 * @covers \PhpMimeMailParser\MimeDecoder
 */
final class MimeDecoderTest extends TestCase
{
    const DATA = [
        ['=?iso-8859-1?Q?HasenundFr=F6sche=2Etxt?=', 'HasenundFrösche.txt'],
        ['=?windows-1250?Q?Automatyczna_odpowied=9F:_Piotrze,_test_z_6_miesi=EAcy_na?=
 =?windows-1250?Q?uki_ci=B9gle_na_Ciebie_czeka?=',
            'Automatyczna odpowiedź: Piotrze, test z 6 miesięcy nauki ciągle na Ciebie czeka'],
        ['=?iso-2022-jp?B?GyRCJygnJS1iGyhCNDEgGyRCJ2AnZBsoQiAyOC4wOS4yMDE2?=', 'ЖД№41 от 28.09.2016'],
        ['=?iso-2022-jp?Q?=1B$B-!-"=1B(B?=', '①②'],
        ['=?iso-8859-8-i?B?7vLw5CAn4PDpIOzgIPDu9uAnOiBJbnZvaWNlIDAyNzIyMDI3?=', "מענה 'אני לא נמצא': Invoice 02722027"],
    ];

    /**
     * @dataProvider provideData
     */
    public function testDecode($input, $expected)
    {
        $decoder = new MimeDecoder(new Charset(), new ContentDecoder());

        $actual = $decoder->decodeHeader($input);

        $this->assertSame($expected, $actual);
    }

    public function provideData()
    {
        return self::DATA;
    }
}
