<?php
namespace Tests\PhpMimeMailParser;

use PhpMimeMailParser\Parser;
use Tests\PhpMimeMailParser\Stubs\AnotherAttachment;

/**
 * Test Attachment of php-mime-mail-parser
 *
 * @covers \PhpMimeMailParser\Parser
 * @covers \PhpMimeMailParser\Attachment
 */
final class AttachmentTest extends TestCase
{
    public function testSaveAttachmentsFromParser()
    {
        $file = __DIR__ . '/mails/m0002';
        $Parser = new Parser();
        $Parser->setPath($file);

        $attachDir = $this->tempdir('m0002_attachments');
        $attachDir .= 'not-yet-existing-directory';

        $Parser->saveAttachments($attachDir);

        $attachmentFiles = glob($attachDir . '*');
        $this->assertCount(1, $attachmentFiles);
    }

    public function testSaveEachAttachment()
    {
        $file = __DIR__ . '/mails/m0002';
        $Parser = new Parser();
        $Parser->setPath($file);

        $this->assertCount(1, $Parser->getAttachments());

        $attachDir = $this->tempdir('m0002_attachments');

        foreach ($Parser->getAttachments() as $attachment) {
            $attachment->save($attachDir);
        }

        $attachmentFiles = glob($attachDir . '*');
        $this->assertCount(1, $attachmentFiles);
    }

    public function testNestedAttachment()
    {
        $file = __DIR__ . '/mails/issue270';
        $Parser = new Parser();
        $Parser->setPath($file);

        $attachments = $Parser->getAttachments(); // Default is GA_INCLUDE_ALL
        $this->assertCount(7, $attachments);
       
        $attachments = $Parser->getAttachments(Parser::GA_INCLUDE_ALL);
        $this->assertCount(7, $attachments);

        // Old version took a boolean - true should be equivalent of ALL
        $attachments = $Parser->getAttachments(true);
        $this->assertCount(7, $attachments);

        $attachments = $Parser->getAttachments(Parser::GA_INCLUDE_NESTED);
        $this->assertCount(6, $attachments);

        // Old version took a boolean - false should be equivalent of NESTED (i.e., all attachments except inline)
        $attachments = $Parser->getAttachments(false);
        $this->assertCount(6, $attachments);

        $attachments = $Parser->getAttachments(Parser::GA_TOPLEVEL);
        $this->assertCount(1, $attachments);

        $attachments = $Parser->getAttachments(Parser::GA_INCLUDE_INLINE);
        $this->assertCount(2, $attachments);
    }

    public function testGeneratingDuplicateSuffixWithoutExtension()
    {
        $file = __DIR__ . '/mails/m0002';
        $Parser = new Parser();
        $Parser->setPath($file);

        $attachDir = $this->tempdir('m0002_attachments');

        $attachments = $Parser->getAttachments();

        $attachments[0]->maxDuplicateNumber = 5;

        for ($i = 1; $i <= 7; $i++) {
            $attachments[0]->save($attachDir);
        }

        $attachmentFiles = glob($attachDir . '*');

        //Original + 5 suffixed + 1 random
        $this->assertCount(7, $attachmentFiles);
        $this->assertFileExists($attachDir . 'attach02');
        $this->assertFileExists($attachDir . 'attach02_1');
        $this->assertFileExists($attachDir . 'attach02_2');
        $this->assertFileExists($attachDir . 'attach02_3');
        $this->assertFileExists($attachDir . 'attach02_4');
        $this->assertFileExists($attachDir . 'attach02_5');
        $this->assertFileNotExists($attachDir . 'attach02_6');
    }

    public function testGeneratingDuplicateSuffix()
    {
        $file = __DIR__ . '/mails/issue115';
        $Parser = new Parser();
        $Parser->setPath($file);

        $attachDir = $this->tempdir('issue115_attachments');

        $attachments = $Parser->getAttachments();

        $attachments[0]->maxDuplicateNumber = 3;

        for ($i = 1; $i <= 5; $i++) {
            $attachments[0]->save($attachDir);
        }

        $attachmentFiles = glob($attachDir . '*');

        //Original + 3 suffixed + 1 random
        $this->assertCount(5, $attachmentFiles);
        $this->assertFileExists($attachDir . 'logo.jpg');
        $this->assertFileExists($attachDir . 'logo_1.jpg');
        $this->assertFileExists($attachDir . 'logo_2.jpg');
        $this->assertFileExists($attachDir . 'logo_3.jpg');
        $this->assertFileNotExists($attachDir . 'logo_4.jpg');
    }

    public function testSavingWithRandomFilenameKeepExtension()
    {
        $file = __DIR__ . '/mails/m0025';
        $Parser = new Parser();
        $Parser->setPath($file);

        $attachDir = $this->tempdir('m0025_attachments');

        $Parser->saveAttachments($attachDir, true, $Parser::ATTACHMENT_RANDOM_FILENAME);

        $attachmentFiles = glob($attachDir . '*');
        $attachmentJpgFiles = glob($attachDir . '*.jpg');
        $attachmentTxtFiles = glob($attachDir . '*.txt');

        $this->assertCount(3, $attachmentFiles);
        $this->assertCount(2, $attachmentJpgFiles);
        $this->assertCount(1, $attachmentTxtFiles);
    }

    public function testUsingAnotherAttachmentClass()
    {
        $file = __DIR__ . '/mails/m0025';
        $Parser = new Parser();
        $Parser->setAttachmentInterface(new AnotherAttachment);
        $Parser->setPath($file);

        $attachments = $Parser->getAttachments();

        $this->assertInstanceOf(AnotherAttachment::class, $attachments[0]);
    }

    public function testIssue236a()
    {
        $file = __DIR__.'/mails/issue236a';
        $attachDir = $this->tempdir('issue236a_attachments');

        $Parser = new Parser();
        $Parser->setText(file_get_contents($file));

        $this->assertStringContainsString('在庫データを添付いたします。', $Parser->getText());
        $this->assertEmpty($Parser->getHtml());

        $attachments = $Parser->getAttachments();
        $this->assertCount(1, $attachments);
        $this->assertEquals('application/octet-stream', $attachments[0]->getContentType());
        $this->assertEquals('ZAIKO.CSV', $attachments[0]->getFilename());
        $attachments[0]->save($attachDir);
        $csv = array_map('str_getcsv', file($attachDir.'ZAIKO.CSV'));
        $this->assertEquals([
            'AS-115',
            '4580514230378',
            '16'
        ], $csv[1]);
    }

    public function testIssue236b()
    {
        $file = __DIR__.'/mails/issue236b';
        $attachDir = $this->tempdir('issue236b_attachments');

        $Parser = new Parser();
        $Parser->setText(file_get_contents($file));

        $this->assertStringContainsString(PHP_EOL, $Parser->getText());
        $this->assertEmpty($Parser->getHtml());

        $attachments = $Parser->getAttachments();
        $this->assertCount(1, $attachments);
        $this->assertEquals('application/vnd.ms-excel', $attachments[0]->getContentType());
        $this->assertEquals('ZAIKO.CSV', $attachments[0]->getFilename());
        $attachments[0]->save($attachDir);
        $csv = array_map('str_getcsv', file($attachDir.'ZAIKO.CSV'));
        $this->assertEquals([
            'AS-115',
            '4580514230378 ',
            '0'
        ], $csv[1]);
    }

    public function testIssue194()
    {
        $file = __DIR__.'/mails/issue194a';
        $attachDir = $this->tempdir('issue194a_attachments');

        $Parser = new Parser();
        $Parser->setText(file_get_contents($file));

        $this->assertStringContainsString('Test now', $Parser->getText());
        $this->assertStringContainsString('Test now', $Parser->getHtml());

        $attachments = $Parser->getAttachments(Parser::GA_TOPLEVEL);
        $this->assertEquals('esate.eml', $attachments[0]->getFilename());
        $this->assertEquals('belangrijk.jpg', $attachments[1]->getFilename());

        $this->assertCount(2, $attachments);


        $file = __DIR__.'/mails/issue194b';
        $attachDir = $this->tempdir('issue194b_attachments');

        $Parser = new Parser();
        $Parser->setText(file_get_contents($file));

        $this->assertStringContainsString('Met vriendelijke groet', $Parser->getText());
        $this->assertStringContainsString('Keizersgracht 15', $Parser->getHtml());

        $attachments = $Parser->getAttachments(Parser::GA_TOPLEVEL);
        $this->assertEquals('belangrijk.jpg', $attachments[0]->getFilename());
        $this->assertEquals('afsdwer.eml', $attachments[1]->getFilename());
        $this->assertEquals('Test a2134.eml', $attachments[2]->getFilename());

        $this->assertCount(3, $attachments);
    }

    public function testIssue125()
    {
        $file = __DIR__.'/mails/issue125';

        $Parser = new Parser();
        $Parser->setText(file_get_contents($file));
        $attachments = $Parser->getAttachments(Parser::GA_INCLUDE_INLINE);

        $this->assertCount(2, $attachments);

        $this->assertEquals('inline', $attachments[0]->getContentDisposition());
        $this->assertEquals('image/png', $attachments[0]->getContentType());
        $this->assertEquals('attachment', $attachments[1]->getContentDisposition());
        $this->assertEquals('message/rfc822', $attachments[1]->getContentType());
    }
}
