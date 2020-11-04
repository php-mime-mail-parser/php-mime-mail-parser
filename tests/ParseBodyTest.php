<?php
namespace Tests\PhpMimeMailParser;

use PhpMimeMailParser\Parser;

/**
 * @covers \PhpMimeMailParser\Parser
 */
final class ParseBodyTest extends TestCase
{
    /**
     * @dataProvider provideBodyEmails
     */
    public function testFromPath($id, $textBody, $htmlBody): void
    {
        $parser = Parser::fromPath(__DIR__.'/emails/'.$id.'.eml');

        $this->generic($parser, $textBody, $htmlBody);
    }

    /**
     * @dataProvider provideBodyEmails
     */
    public function testFromText($id, $textBody, $htmlBody): void
    {
        $parser = Parser::fromText(file_get_contents(__DIR__.'/emails/'.$id.'.eml'));

        $this->generic($parser, $textBody, $htmlBody);
    }

    /**
     * @dataProvider provideBodyEmails
     */
    public function testFromStream($id, $textBody, $htmlBody): void
    {
        $parser = Parser::fromStream(fopen(__DIR__.'/emails/'.$id.'.eml', 'r'));

        $this->generic($parser, $textBody, $htmlBody);
    }

    private function generic($parser, $textBody, $htmlBody): void
    {
        //Test  Body : text
        if ($textBody['matchType'] == 'PARTIAL') {
            $this->assertStringContainsString($textBody['expectedValue'], $parser->getText());
        } elseif ($textBody['matchType'] == 'EXACT') {
            $this->assertEquals($textBody['expectedValue'], $parser->getText());
        }

        //Test Body : html
        if ($htmlBody['matchType'] == 'PARTIAL') {
            $this->assertStringContainsString($htmlBody['expectedValue'], $parser->getHtmlNotEmbedded());
        } elseif ($htmlBody['matchType'] == 'EXACT') {
            $this->assertEquals($htmlBody['expectedValue'], $parser->getHtmlNotEmbedded());
        }
    }
}
