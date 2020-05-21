<?php
namespace Tests\PhpMimeMailParser;

use PhpMimeMailParser\Charset;
use PhpMimeMailParser\ContentTransferDecoder;
use PhpMimeMailParser\MimeHeaderDecoder;

/**
 * @covers \PhpMimeMailParser\MimeHeaderDecoder
 */
final class MimeHeaderDecoderTest extends TestCase
{
    const DATA = [
        ['=?iso-8859-1?Q?HasenundFr=F6sche=2Etxt?=', 'HasenundFrösche.txt'],
        ['=?windows-1250?Q?Automatyczna_odpowied=9F:_Piotrze,_test_z_6_miesi=EAcy_na?=
 =?windows-1250?Q?uki_ci=B9gle_na_Ciebie_czeka?=',
            'Automatyczna odpowiedź: Piotrze, test z 6 miesięcy nauki ciągle na Ciebie czeka'],
        ['=?iso-2022-jp?B?GyRCJygnJS1iGyhCNDEgGyRCJ2AnZBsoQiAyOC4wOS4yMDE2?=', 'ЖД№41 от 28.09.2016'],
        ['=?iso-2022-jp?Q?=1B$B-!-"=1B(B?=', '①②'],
        ['=?iso-8859-8-i?B?7vLw5CAn4PDpIOzgIPDu9uAnOiBJbnZvaWNlIDAyNzIyMDI3?=', "מענה 'אני לא נמצא': Invoice 02722027"],
        ['=?iso-8859-8-i?q?Test_message?=', 'Test message'],
        ['=?ISO-8859-1?Q?Mail_avec_fichier_attach=E9_de_1ko?=', 'Mail avec fichier attaché de 1ko'],
        ['=?windows-1251?Q?occurs_when_divided_into_an_array?= =?windows-1251?Q?=2C_and_the_last_e_of_the_array!_?=',
            'occurs when divided into an array, and the last e of the array! '],
        ['=?US-ASCII?Q?Katerine_Moore?=', 'Katerine Moore'],
        ['=?UTF-8?Q?Biodiversit=C3=A9_de_semaine_en_semaine.doc?=', 'Biodiversité de semaine en semaine.doc'],
    ];

    /**
     * @dataProvider provideData
     */
    public function testDecode($input, $expected)
    {
        $decoder = new MimeHeaderDecoder(new Charset(), new ContentTransferDecoder());

        $actual = $decoder->decodeHeader($input);

        $this->assertSame($expected, $actual);
    }

    public function provideData()
    {
        return self::DATA;
    }
}
