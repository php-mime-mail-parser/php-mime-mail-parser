<?php
namespace Tests\PhpMimeMailParser;

use PhpMimeMailParser\Parser;

/**
 * @covers \PhpMimeMailParser\Parser
 */
final class ParseHeaderTest extends TestCase
{

    /**
     * @dataProvider provideEmails
     */
    public function testFromPath($id, $subject, $from, $to, $cc, $textBody, $htmlBody, $attachments): void
    {
        $parser = Parser::fromPath(__DIR__.'/emails/'.$id.'.eml');

        $this->generic($parser, $id, $subject, $from, $to, $cc, $textBody, $htmlBody, $attachments);
    }

    /**
     * @dataProvider provideEmails
     */
    public function testFromText($id, $subject, $from, $to, $cc, $textBody, $htmlBody, $attachments): void
    {
        $parser = Parser::fromText(file_get_contents(__DIR__.'/emails/'.$id.'.eml'));

        $this->generic($parser, $id, $subject, $from, $to, $cc, $textBody, $htmlBody, $attachments);
    }

    /**
     * @dataProvider provideEmails
     */
    public function testFromStream($id, $subject, $from, $to, $cc, $textBody, $htmlBody, $attachments): void
    {
        $parser = Parser::fromStream(fopen(__DIR__.'/emails/'.$id.'.eml', 'r'));

        $this->generic($parser, $id, $subject, $from, $to, $cc, $textBody, $htmlBody, $attachments);
    }

    private function generic($parser, $id, $subject, $from, $to, $cc, $textBody, $htmlBody, $attachments): void
    {
        $attachDir = $this->tempdir("attachments_$id");

        //Test Header : subject
        $this->assertEquals($subject, $parser->getSubject());
        $this->assertArrayHasKey('subject', $parser->getHeaders());

        //Test Header : from
        $this->assertEquals($from['name'], $parser->getAddressesFrom()[0]['display']);
        $this->assertEquals($from['email'], $parser->getAddressesFrom()[0]['address']);
        $this->assertEquals($from['is_group'], $parser->getAddressesFrom()[0]['is_group']);
        $this->assertEquals($from['header_value'], $parser->getFrom());
        $this->assertArrayHasKey('from', $parser->getHeaders());

        //Test Header : to
        foreach ($to as $key => $inbox) {
            if (is_array($inbox)) {
                $this->assertEquals($inbox['name'], $parser->getAddressesTo()[$key]['display']);
                $this->assertEquals($inbox['email'], $parser->getAddressesTo()[$key]['address']);
                $this->assertEquals($inbox['is_group'], $parser->getAddressesTo()[$key]['is_group']);
            }
        }
        $this->assertEquals($to['header_value'], $parser->getHeader('to'));
        $this->assertArrayHasKey('to', $parser->getHeaders());

        //Test Header : cc
        if ($cc) {
            $this->assertEquals($cc, $parser->getHeaders()['cc']);
        }
    }

    public function testInvalidHeader()
    {
        $parser = Parser::fromPath(__DIR__.'/emails/m001.eml');

        $this->assertNull($parser->getHeader('azerty'));
        $this->assertArrayNotHasKey('azerty', $parser->getHeaders());
    }

    public function testRawHeaders()
    {
        $parser = Parser::fromPath(__DIR__.'/emails/m001.eml');

        $this->assertIsArray($parser->getHeadersRaw());
    }
}
