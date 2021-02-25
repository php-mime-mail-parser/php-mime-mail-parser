<?php

namespace {

    use PhpMimeMailParser\Parser;
    use PhpMimeMailParser\Attachment;
    use PhpMimeMailParser\Exception;

    // This allow us to configure the behavior of the "global mock"
    $mockTmpFile = false;
    $mockFopen = false;
}

namespace PhpMimeMailParser {

    function tmpfile()
    {
        global $mockTmpFile;
        if (isset($mockTmpFile) && $mockTmpFile === true) {
            return false;
        } else {
            return call_user_func_array('\tmpfile', func_get_args());
        }
    }

    function fopen()
    {
        global $mockFopen;
        if (isset($mockFopen) && $mockFopen === true) {
            return false;
        } else {
            return call_user_func_array('\fopen', func_get_args());
        }
    }

    /**
     * ExceptionTest of php-mime-mail-parser
     *
     * Fully Tested Mailparse Extension Wrapper for PHP 5.4+
     *
     */

    class ExceptionTest extends \PHPUnit\Framework\TestCase
    {
        public function setUp(): void
        {
            global $mockTmpFile;
            $mockTmpFile = false;

            global $mockFopen;
            $mockFopen = false;
        }

        public function testGetHeader()
        {
            $this->expectException(Exception::class);
            $this->expectExceptionMessage('setPath() or setText() or setStream() must be called before');

            $Parser = new Parser();
            $Parser->getHeader('test');
        }

        public function testGetHeaders()
        {
            $this->expectException(Exception::class);
            $this->expectExceptionMessage('setPath() or setText() or setStream() must be called before');

            $Parser = new Parser();
            $Parser->getHeaders();
        }

        public function testGetHeadersRaw()
        {
            $this->expectException(Exception::class);
            $this->expectExceptionMessage('setPath() or setText() or setStream() must be called before');

            $Parser = new Parser();
            $Parser->getHeadersRaw();
        }

        public function testgetMessageBody()
        {
            $this->expectException(Exception::class);
            $this->expectExceptionMessage('Invalid type specified for getMessageBody(). Expected: text, html or htmlEmbeded.');

            $Parser = new Parser();
            $Parser->getMessageBody('azerty');
        }

        public function testgetInlineParts()
        {
            $this->expectException(Exception::class);
            $this->expectExceptionMessage('Invalid type specified for getInlineParts(). "type" can either be text or html.');

            $Parser = new Parser();
            $Parser->getInlineParts('azerty');
        }

        public function testSetText()
        {
            $this->expectException(Exception::class);
            $this->expectExceptionMessage('You must not call MimeMailParser::setText with an empty string parameter');

            $Parser = new Parser();
            $Parser->setText('');
        }

        public function testSetStream()
        {
            $this->expectException(Exception::class);
            $this->expectExceptionMessage('setStream() expects parameter stream to be readable stream resource.');

            $Parser = new Parser();
            $Parser->setStream('azerty');
        }

        public function testSetStreamWithoutTmpPermissions()
        {
            $this->expectException(Exception::class);
            $this->expectExceptionMessage('Could not create temporary files for attachments.');

            global $mockTmpFile;
            $mockTmpFile = true;

            $file = __DIR__.'/mails/m0001';

            $Parser = new Parser();
            $Parser->setStream(fopen($file, 'r'));
        }

        public function testGetAttachmentStreamWithoutTmpPermissions()
        {
            $this->expectException(Exception::class);
            $this->expectExceptionMessage('Could not create temporary files for attachments.');

            $file = __DIR__.'/mails/m0001';

            $Parser = new Parser();
            $Parser->setStream(fopen($file, 'r'));

            global $mockTmpFile;
            $mockTmpFile = true;

            $attachments = $Parser->getAttachments();
        }

        public function testSetStreamResource()
        {
            $this->expectException(Exception::class);
            $this->expectExceptionMessage('setStream() expects parameter stream to be readable stream resource.');

            $c = socket_create(AF_UNIX, SOCK_STREAM, 0);
            $Parser = new Parser();
            $Parser->setStream($c);
        }

        public function testSaveAttachmentsWithoutPermissions()
        {
            $this->expectException(Exception::class);
            $this->expectExceptionMessage('Could not write attachments. Your directory may be unwritable by PHP.');

            $mid = 'm0001';
            $file = __DIR__.'/mails/'.$mid;
            $attach_dir = __DIR__.'/mails/attach_'.$mid.'/';

            $Parser = new Parser();
            $Parser->setStream(fopen($file, 'r'));

            global $mockFopen;
            $mockFopen = true;

            $Parser->saveAttachments($attach_dir);
        }

        public function testSaveAttachmentsWithDuplicateNames()
        {
            $this->expectException(Exception::class);
            $this->expectExceptionMessage('Could not create file for attachment: duplicate filename.');

            $mid = 'm0026';
            $file = __DIR__ . '/mails/' . $mid;
            $attach_dir = __DIR__ . '/mails/attach_' . $mid . '/';

            $Parser = new Parser();
            $Parser->setText(file_get_contents($file));

            try {
                $Parser->saveAttachments($attach_dir, false, Parser::ATTACHMENT_DUPLICATE_THROW);
            } catch (Exception $e) {
                // Clean up attachments dir
                unlink($attach_dir . 'ATT00001.txt');
                rmdir($attach_dir);

                throw $e;
            }
        }

        public function testSaveAttachmentsInvalidStrategy()
        {
            $this->expectException(Exception::class);
            $this->expectExceptionMessage('Invalid filename strategy argument provided.');

            $file = __DIR__ . '/mails/m0026';

            $Parser = new Parser();
            $Parser->setText(file_get_contents($file));

            $Parser->saveAttachments('dir', false, 'InvalidValue');
        }
    }
}
