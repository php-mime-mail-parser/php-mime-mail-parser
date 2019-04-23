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

    public function testSaveEachAttachmentDuplicateSuffix()
    {
        $file = __DIR__ . '/mails/m0002';
        $Parser = new Parser();
        $Parser->setPath($file);

        $attachDir = __DIR__ . '/mails/m0002_attachments/';
        $attachments = $Parser->getAttachments();

        for ($i = 0; $i <= 1001; $i++) {
            $attachments[0]->save($attachDir);
        }

        $attachmentFiles = glob($attachDir . '*');

        //Original + 1000 suffixed + 1 random
        $this->assertEquals(1002, count($attachmentFiles));
        $this->assertFileExists($attachDir . 'attach02');
        $this->assertFileExists($attachDir . 'attach02_1');
        $this->assertFileExists($attachDir . 'attach02_500');
        $this->assertFileExists($attachDir . 'attach02_1000');
        $this->assertFileNotExists($attachDir . 'attach02_1001');

        // Clean up attachments dir
        array_map('unlink', $attachmentFiles);
        rmdir($attachDir);
    }
}
