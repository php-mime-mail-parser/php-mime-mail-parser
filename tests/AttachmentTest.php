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

        $filePath = "$attachDir/{$attachments[0]->getFilename()}";
        $this->assertTrue(file_exists($filePath));
        $this->assertTrue(file_exists("{$filePath}_1000"));

        $attachmentFiles = glob($attachDir . '*');

        // Clean up attachments dir
        array_map('unlink', $attachmentFiles);
        rmdir($attachDir);

        $this->assertCount(1002, $attachmentFiles); //Original + 1000 suffixed + 1 random
    }
}
