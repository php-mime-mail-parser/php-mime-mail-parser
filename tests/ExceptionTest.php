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
    public function testGetHeader()
    {
        $Parser = new Parser();

        $this->expectException(\PhpMimeMailParser\Exception::class);
        $this->expectExceptionMessage('setPath() or setText() or setStream() must be called before');

        $Parser->getHeader('test');
    }

    /**
     */
    public function testGetHeaders()
    {
        $Parser = new Parser();

        $this->expectException(\PhpMimeMailParser\Exception::class);
        $this->expectExceptionMessage('setPath() or setText() or setStream() must be called before');

        $Parser->getHeaders();
    }

    /**
     */
    public function testGetHeadersRaw()
    {
        $Parser = new Parser();

        $this->expectException(\PhpMimeMailParser\Exception::class);
        $this->expectExceptionMessage('setPath() or setText() or setStream() must be called before');

        $Parser->getHeadersRaw();
    }

    /**
     */
    public function testgetMessageBody()
    {
        $Parser = new Parser();

        $this->expectException(\PhpMimeMailParser\Exception::class);
        $this->expectExceptionMessage(
            'Invalid type specified for getMessageBody(). Expected: text, html or htmlEmbeded.'
        );

        $Parser->getMessageBody('azerty');
    }

    /**
     */
    public function testgetInlineParts()
    {
        $Parser = new Parser();

        $this->expectException(\PhpMimeMailParser\Exception::class);
        $this->expectExceptionMessage(
            'Invalid type specified for getInlineParts(). "type" can either be text or html.'
        );

        $Parser->getInlineParts('azerty');
    }


    /**
     */
    public function testSetText()
    {
        $Parser = new Parser();

        $this->expectException(\PhpMimeMailParser\Exception::class);
        $this->expectExceptionMessage('You must not call MimeMailParser::setText with an empty string parameter');

        $Parser->setText('');
    }

    /**
     */
    public function testSetStream()
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
    public function testSetStreamWithoutTmpPermissions()
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
    public function testSetStreamResource()
    {
        $c = socket_create(AF_UNIX, SOCK_STREAM, 0);
        $Parser = new Parser();

        $this->expectException(\PhpMimeMailParser\Exception::class);
        $this->expectExceptionMessage('setStream() expects parameter stream to be readable stream resource.');

        $Parser->setStream($c);
    }

    /**
     */
    public function testSaveAttachmentsWithoutPermissions()
    {
        $mid = 'm0001';
        $file = __DIR__.'/mails/'.$mid;
        $attach_dir = $this->tempdir('attach_'.$mid);
        chmod($attach_dir, 0600);

        $Parser = new Parser();
        $Parser->setStream(fopen($file, 'r'));

        $this->expectException(\PhpMimeMailParser\Exception::class);
        $this->expectExceptionMessage('Could not write attachments. Your directory may be unwritable by PHP.');

        $Parser->saveNestedAttachments($attach_dir, ['attachment', 'inline']);
    }

    /**
     */
    public function testSaveAttachmentsWithDuplicateNames()
    {
        $mid = 'm0026';
        $file = __DIR__ . '/mails/' . $mid;
        $attach_dir = $this->tempdir('attach_' . $mid);

        $Parser = new Parser();
        $Parser->setText(file_get_contents($file));

        $this->expectException(\PhpMimeMailParser\Exception::class);
        $this->expectExceptionMessage('Could not create file for attachment: duplicate filename.');

        $Parser->saveNestedAttachments($attach_dir, ['attachment'], Parser::ATTACHMENT_DUPLICATE_THROW);
    }

    /**
     */
    public function testSaveAttachmentsInvalidStrategy()
    {
        $file = __DIR__ . '/mails/m0026';

        $Parser = new Parser();
        $Parser->setText(file_get_contents($file));

        $this->expectException(\PhpMimeMailParser\Exception::class);
        $this->expectExceptionMessage('Invalid filename strategy argument provided.');

        $Parser->saveNestedAttachments('dir', ['attachment'], 'InvalidValue');
    }
}
