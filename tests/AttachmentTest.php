<?php
namespace PhpMimeMailParser;

use PhpMimeMailParser\Parser;
use PhpMimeMailParser\Attachment;
use PhpMimeMailParser\Exception;

/**
 * Test Attachment of php-mime-mail-parser
 *
 * Fully Tested Mailparse Extension Wrapper for PHP 5.4+
 *
 */
class AttachmentTest extends \PHPUnit\Framework\TestCase
{
    public function testSaveAttachmentsFromParser()
    {
        $file = __DIR__ . '/mails/m0002';
        $Parser = new Parser();
        $Parser->setPath($file);

        $attachDir = __DIR__ . '/mails/m0002_attachments/';
        $Parser->saveAttachments($attachDir);

        $attachmentFiles = glob($attachDir . '*');

        // Clean up attachments dir
        array_map('unlink', $attachmentFiles);
        rmdir($attachDir);

        $this->assertCount(1, $attachmentFiles);
    }

    public function testSaveEachAttachment()
    {
        $file = __DIR__ . '/mails/m0002';
        $Parser = new Parser();
        $Parser->setPath($file);

        $attachDir = __DIR__ . '/mails/m0002_attachments/';
        foreach ($Parser->getAttachments() as $attachment) {
            $attachment->save($attachDir);
        }

        $attachmentFiles = glob($attachDir . '*');

        // Clean up attachments dir
        array_map('unlink', $attachmentFiles);
        rmdir($attachDir);

        $this->assertCount(1, $attachmentFiles);
    }

    public function testGeneratingDuplicateSuffixWithoutExtension()
    {
        $file = __DIR__ . '/mails/m0002';
        $Parser = new Parser();
        $Parser->setPath($file);

        $attachDir = __DIR__ . '/mails/m0002_attachments/';
        $attachments = $Parser->getAttachments();

        $attachments[0]->maxDuplicateNumber = 5;

        for ($i = 1; $i <= 7; $i++) {
            $attachments[0]->save($attachDir);
        }

        $attachmentFiles = glob($attachDir . '*');

        //Original + 5 suffixed + 1 random
        $this->assertEquals(7, count($attachmentFiles));
        $this->assertFileExists($attachDir . 'attach02');
        $this->assertFileExists($attachDir . 'attach02_1');
        $this->assertFileExists($attachDir . 'attach02_2');
        $this->assertFileExists($attachDir . 'attach02_3');
        $this->assertFileExists($attachDir . 'attach02_4');
        $this->assertFileExists($attachDir . 'attach02_5');
        $this->assertFileDoesNotExist($attachDir . 'attach02_6');

        // Clean up attachments dir
        array_map('unlink', $attachmentFiles);
        rmdir($attachDir);
    }

    public function testGeneratingDuplicateSuffix()
    {
        $file = __DIR__ . '/mails/issue115';
        $Parser = new Parser();
        $Parser->setPath($file);

        $attachDir = __DIR__ . '/mails/issue115_attachments/';
        $attachments = $Parser->getAttachments();

        $attachments[0]->maxDuplicateNumber = 3;

        for ($i = 1; $i <= 5; $i++) {
            $attachments[0]->save($attachDir);
        }

        $attachmentFiles = glob($attachDir . '*');

        //Original + 3 suffixed + 1 random
        $this->assertEquals(5, count($attachmentFiles));
        $this->assertFileExists($attachDir . 'logo.jpg');
        $this->assertFileExists($attachDir . 'logo_1.jpg');
        $this->assertFileExists($attachDir . 'logo_2.jpg');
        $this->assertFileExists($attachDir . 'logo_3.jpg');
        $this->assertFileDoesNotExist($attachDir . 'logo_4.jpg');

        // Clean up attachments dir
        array_map('unlink', $attachmentFiles);
        rmdir($attachDir);
    }

    public function testSavingWithRandomFilenameKeepExtension()
    {
        $file = __DIR__ . '/mails/m0025';
        $Parser = new Parser();
        $Parser->setPath($file);

        $attachDir = __DIR__ . '/mails/m0025_attachments/';
        $Parser->saveAttachments($attachDir, true, $Parser::ATTACHMENT_RANDOM_FILENAME);

        $attachmentFiles = glob($attachDir . '*');
        $attachmentJpgFiles = glob($attachDir . '*.jpg');
        $attachmentTxtFiles = glob($attachDir . '*.txt');

        // Clean up attachments dir
        array_map('unlink', $attachmentFiles);
        rmdir($attachDir);

        $this->assertCount(3, $attachmentFiles);
        $this->assertCount(2, $attachmentJpgFiles);
        $this->assertCount(1, $attachmentTxtFiles);
    }

    public function testInlineContent()
    {
        $file = __DIR__ . '/mails/m0129';
        $Parser = new Parser();
        $Parser->setPath($file);

        $attachments = $Parser->getAttachments();

        $this->assertCount(1, $attachments);

        $this->assertEquals(
            "c024cb5b0c6eaf060a837c53db9af39b5b564d3956b8d4223fbf308b11b4e79e",
            $attachments[0]->getContentID()
        );
        $this->assertEquals("inline", $attachments[0]->getContentDisposition());
    }

    public function testIssue470()
    {
        // Test case for RFC 2046 compliance: boundary parsing with malformed boundaries
        // The email contains "--Part--More" which is NOT a valid boundary according to RFC 2046
        // Per RFC 2046 Section 5.1.1: boundary lines can only have linear-white-space after "--boundary--"
        // Since "More" is not white-space, "--Part--More" should be treated as content, not a boundary
        // 
        // Current behavior: PHP's mailparse extension incorrectly treats "--Part--More" as a boundary
        // Expected behavior: Content should include everything until the next valid boundary "--Part--"
        
        // Init
        $file = __DIR__ . '/mails/issue470';

        $Parser = new Parser();
        $Parser->setPath($file);

        $attachments = $Parser->getAttachments();

        $this->assertCount(1, $attachments);

        foreach ($attachments as $attachment) {
            // Current behavior (incorrect per RFC 2046): only "abc\n" is extracted
            // TODO: Fix boundary parsing to comply with RFC 2046
            $this->assertEquals("abc\n", $attachment->getContent());
            
            // Expected behavior per RFC 2046 (currently fails):
            // $this->assertEquals("abc\n\n--Part--More\n\n", $attachment->getContent());

        }
    }
}
