<?php

namespace Tests\PhpMimeMailParser;

use PhpMimeMailParser\Parser;

/**
 * ExceptionTest of php-mime-mail-parser
 *
 * @covers \PhpMimeMailParser\Parser
 * @covers \PhpMimeMailParser\Attachment
 */
final class ExceptionTest extends TestCase
{
    /**
     */
    public function testFromText(): void
    {
        $this->expectException(\PhpMimeMailParser\Exception::class);
        $this->expectExceptionMessage('You must not call fromText with an empty string parameter');

        $Parser = Parser::fromText('');
    }

    /**
     */
    public function testSetStream(): void
    {
        $this->expectException(\PhpMimeMailParser\Exception::class);
        $this->expectExceptionMessage('setStream() expects parameter stream to be readable stream resource.');

        $Parser = Parser::fromStream('azerty');
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testSetStreamWithoutTmpPermissions(): void
    {
        putenv('TMPDIR=/invalid');

        $file = __DIR__.'/mails/m0001';

        $this->expectException(\PhpMimeMailParser\Exception::class);
        $this->expectExceptionMessage('Could not create temporary files for attachments.');

        $Parser = Parser::fromStream(fopen($file, 'r'));
    }

    /**
     */
    public function testSetStreamResource(): void
    {
        $c = socket_create(AF_UNIX, SOCK_STREAM, 0);

        $this->expectException(\PhpMimeMailParser\Exception::class);
        $this->expectExceptionMessage('setStream() expects parameter stream to be readable stream resource.');

        $Parser = Parser::fromStream($c);
    }

    /**
     */
    public function testSaveAttachmentsWithoutPermissions(): void
    {
        $mid = 'm0001';
        $file = __DIR__.'/mails/'.$mid;
        $attachDir = $this->tempdir('attach_'.$mid);
        chmod($attachDir, 0600);

        $Parser = Parser::fromStream(fopen($file, 'r'));

        $this->expectException(\PhpMimeMailParser\Exception::class);
        $this->expectExceptionMessage('Could not write attachments. Your directory may be unwritable by PHP.');

        $Parser->saveNestedAttachments($attachDir, ['attachment', 'inline']);
    }

    /**
     */
    public function testSaveAttachmentsWithDuplicateNames(): void
    {
        $mid = 'm0026';
        $file = __DIR__ . '/mails/' . $mid;
        $attachDir = $this->tempdir('attach_' . $mid);

        $Parser = Parser::fromText(file_get_contents($file));

        $this->expectException(\PhpMimeMailParser\Exception::class);
        $this->expectExceptionMessage('Could not create file for attachment: duplicate filename.');

        $Parser->saveNestedAttachments($attachDir, ['attachment'], Parser::ATTACHMENT_DUPLICATE_THROW);
    }

    /**
     */
    public function testSaveAttachmentsInvalidStrategy(): void
    {
        $file = __DIR__ . '/mails/m0026';
        $Parser = Parser::fromText(file_get_contents($file));

        $this->expectException(\PhpMimeMailParser\Exception::class);
        $this->expectExceptionMessage('Invalid filename strategy argument provided.');

        $Parser->saveNestedAttachments('dir', ['attachment'], 'InvalidValue');
    }
}
