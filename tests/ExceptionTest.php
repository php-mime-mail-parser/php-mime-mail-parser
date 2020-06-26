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
    public function testGetHeader(): void
    {
        $Parser = new Parser();

        $this->expectException(\PhpMimeMailParser\Exception::class);
        $this->expectExceptionMessage('setPath() or setText() or setStream() must be called before');

        $Parser->getHeader('test');
    }

    /**
     */
    public function testGetHeaders(): void
    {
        $Parser = new Parser();

        $this->expectException(\PhpMimeMailParser\Exception::class);
        $this->expectExceptionMessage('setPath() or setText() or setStream() must be called before');

        $Parser->getHeaders();
    }

    /**
     */
    public function testGetHeadersRaw(): void
    {
        $Parser = new Parser();

        $this->expectException(\PhpMimeMailParser\Exception::class);
        $this->expectExceptionMessage('setPath() or setText() or setStream() must be called before');

        $Parser->getHeadersRaw();
    }


    /**
     */
    public function testSetText(): void
    {
        $Parser = new Parser();

        $this->expectException(\PhpMimeMailParser\Exception::class);
        $this->expectExceptionMessage('You must not call MimeMailParser::setText with an empty string parameter');

        $Parser->setText('');
    }

    /**
     */
    public function testSetStream(): void
    {
        $Parser = new Parser();

        $this->expectException(\PhpMimeMailParser\Exception::class);
        $this->expectExceptionMessage('setStream() expects parameter stream to be readable stream resource.');

        $Parser->setStream('azerty');
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testSetStreamWithoutTmpPermissions(): void
    {
        putenv('TMPDIR=/invalid');

        $file = __DIR__.'/mails/m0001';
        $Parser = new Parser();

        $this->expectException(\PhpMimeMailParser\Exception::class);
        $this->expectExceptionMessage('Could not create temporary files for attachments.');

        $Parser->setStream(fopen($file, 'r'));
    }

    /**
     */
    public function testSetStreamResource(): void
    {
        $c = socket_create(AF_UNIX, SOCK_STREAM, 0);
        $Parser = new Parser();

        $this->expectException(\PhpMimeMailParser\Exception::class);
        $this->expectExceptionMessage('setStream() expects parameter stream to be readable stream resource.');

        $Parser->setStream($c);
    }

    /**
     */
    public function testSaveAttachmentsWithoutPermissions(): void
    {
        $mid = 'm0001';
        $file = __DIR__.'/mails/'.$mid;
        $attachDir = $this->tempdir('attach_'.$mid);
        chmod($attachDir, 0600);

        $Parser = new Parser();
        $Parser->setStream(fopen($file, 'r'));

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

        $Parser = new Parser();
        $Parser->setText(file_get_contents($file));

        $this->expectException(\PhpMimeMailParser\Exception::class);
        $this->expectExceptionMessage('Could not create file for attachment: duplicate filename.');

        $Parser->saveNestedAttachments($attachDir, ['attachment'], Parser::ATTACHMENT_DUPLICATE_THROW);
    }

    /**
     */
    public function testSaveAttachmentsInvalidStrategy(): void
    {
        $file = __DIR__ . '/mails/m0026';

        $Parser = new Parser();
        $Parser->setText(file_get_contents($file));

        $this->expectException(\PhpMimeMailParser\Exception::class);
        $this->expectExceptionMessage('Invalid filename strategy argument provided.');

        $Parser->saveNestedAttachments('dir', ['attachment'], 'InvalidValue');
    }
}
