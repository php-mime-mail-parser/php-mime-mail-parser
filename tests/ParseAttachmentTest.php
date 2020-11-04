<?php
namespace Tests\PhpMimeMailParser;

use PhpMimeMailParser\Parser;

/**
 * @covers \PhpMimeMailParser\Parser
 */
final class ParseAttachmentTest extends TestCase
{
    /**
     * @dataProvider provideAttachmentEmails
     */
    public function testFromPath($id, $attachments): void
    {
        $parser = Parser::fromPath(__DIR__.'/emails/'.$id.'.eml');

        $this->generic($parser, $id, $attachments);
    }

    /**
     * @dataProvider provideAttachmentEmails
     */
    public function testFromText($id, $attachments): void
    {
        $parser = Parser::fromText(file_get_contents(__DIR__.'/emails/'.$id.'.eml'));

        $this->generic($parser, $id, $attachments);
    }

    /**
     * @dataProvider provideAttachmentEmails
     */
    public function testFromStream($id, $attachments): void
    {
        $parser = Parser::fromStream(fopen(__DIR__.'/emails/'.$id.'.eml', 'r'));

        $this->generic($parser, $id, $attachments);
    }

    private function generic($parser, $id, $attachments): void
    {
        $attachDir = $this->tempdir("attachments_$id");

        //Test Nb Attachments
        $a = $parser->getNestedAttachments(['inline', 'attachment']);
        $this->assertEquals(count($attachments), count($a));
        $iterAttachments = 0;

        //Test Attachments
        $attachmentsEmbeddedToCheck = [];
        if (count($attachments) > 0) {
            //Save attachments
            $parser->saveNestedAttachments($attachDir, ['attachment', 'inline']);

            foreach ($attachments as $attachment) {
                //Test Exist Attachment
                $this->assertFileExists($attachDir.$attachment['fileName']);

                //Test Filename Attachment
                $this->assertEquals($attachment['fileName'], $a[$iterAttachments]->getFilename());

                //Test Size Attachment
                $this->assertEquals(
                    $attachment['size'],
                    filesize($attachDir.$a[$iterAttachments]->getFilename())
                );

                //Test Inside Attachment
                if ($attachment['fileContent']) {
                    $fileContent = file_get_contents(
                        $attachDir.$a[$iterAttachments]->getFilename(),
                        true
                    );

                    if ($attachment['fileContent']['matchType'] == 'PARTIAL') {
                        $this->assertStringContainsString($attachment['fileContent']['expectedValue'], $fileContent);
                        $this->assertStringContainsString($attachment['fileContent']['expectedValue'], $a[$iterAttachments]->getContent());
                    } elseif ($attachment['fileContent']['matchType'] == 'EXACT') {
                        $this->assertEquals($attachment['fileContent']['expectedValue'], $fileContent);
                        $this->assertEquals($attachment['fileContent']['expectedValue'], $a[$iterAttachments]->getContent());
                    }
                }

                //Test ContentType Attachment
                $this->assertEquals($attachment['contentType'], $a[$iterAttachments]->getContentType());

                //Test ContentDisposition Attachment
                $this->assertEquals($attachment['contentDisposition'], $a[$iterAttachments]->getContentDisposition());

                //Test md5 of Headers Attachment
                $this->assertEquals(
                    $attachment['hashHeader'],
                    md5(serialize($a[$iterAttachments]->getHeaders()))
                );

                //Save embedded Attachments to check
                if ($attachment['hashEmbeddedContent']) {
                    $attachmentsEmbeddedToCheck[] = $attachment['hashEmbeddedContent'];
                }

                $iterAttachments++;
            }
        } else {
            $this->assertEquals([], $parser->saveNestedAttachments($attachDir, ['attachment', 'inline']));
        }

        //Test embedded Attachments
        $htmlEmbedded = $parser->getHtml();
        $this->assertEquals(count($attachmentsEmbeddedToCheck), substr_count($htmlEmbedded, "data:"));

        foreach ($attachmentsEmbeddedToCheck as $itemExpected) {
            $this->assertEquals(1, substr_count($htmlEmbedded, $itemExpected));
        }
    }
}
