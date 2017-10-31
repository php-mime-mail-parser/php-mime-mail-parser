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

    class ExceptionTest extends \PHPUnit_Framework_TestCase
    {
        public function setUp()
        {
            global $mockTmpFile;
            $mockTmpFile = false;

            global $mockFopen;
            $mockFopen = false;
        }

        /**
         * @expectedException        Exception
         * @expectedExceptionMessage setPath() or setText() or setStream() must be called before
         */
        public function testGetHeader()
        {
            $Parser = new Parser();
            $Parser->getHeader('test');
        }

        /**
         * @expectedException        Exception
         * @expectedExceptionMessage setPath() or setText() or setStream() must be called before
         */
        public function testGetHeaders()
        {
            $Parser = new Parser();
            $Parser->getHeaders();
        }

        /**
         * @expectedException        Exception
         * @expectedExceptionMessage setPath() or setText() or setStream() must be called before
         */
        public function testGetHeadersRaw()
        {
            $Parser = new Parser();
            $Parser->getHeadersRaw();
        }

        /**
         * @expectedException        Exception
         * @expectedExceptionMessage Invalid type specified for getMessageBody(). Expected: text, html or htmlEmbeded.
         */
        public function testgetMessageBody()
        {
            $Parser = new Parser();
            $Parser->getMessageBody('azerty');
        }

        /**
         * @expectedException        Exception
         * @expectedExceptionMessage Invalid type specified for getInlineParts(). "type" can either be text or html.
         */
        public function testgetInlineParts()
        {
            $Parser = new Parser();
            $Parser->getInlineParts('azerty');
        }


        /**
         * @expectedException        Exception
         * @expectedExceptionMessage You must not call MimeMailParser::setText with an empty string parameter
         */
        public function testSetText()
        {
            $Parser = new Parser();
            $Parser->setText('');
        }

        /**
         * @expectedException        Exception
         * @expectedExceptionMessage setStream() expects parameter stream to be readable stream resource.
         */
        public function testSetStream()
        {
            $Parser = new Parser();
            $Parser->setStream('azerty');
        }

        /**
         * @expectedException        Exception
         * @expectedExceptionMessage Could not create temporary files for attachments.
         */
        public function testSetStreamWithoutTmpPermissions()
        {
            global $mockTmpFile;
            $mockTmpFile = true;

            $file = __DIR__.'/mails/m0001';

            $Parser = new Parser();
            $Parser->setStream(fopen($file, 'r'));
        }

        /**
         * @expectedException        Exception
         * @expectedExceptionMessage Could not create temporary files for attachments.
         */
        public function testGetAttachmentStreamWithoutTmpPermissions()
        {

            $file = __DIR__.'/mails/m0001';

            $Parser = new Parser();
            $Parser->setStream(fopen($file, 'r'));

            global $mockTmpFile;
            $mockTmpFile = true;

            $attachments = $Parser->getAttachments();
        }

        /**
         * @expectedException        Exception
         * @expectedExceptionMessage setStream() expects parameter stream to be readable stream resource.
         */
        public function testSetStreamResource()
        {
            $c = socket_create(AF_UNIX, SOCK_STREAM, 0);
            $Parser = new Parser();
            $Parser->setStream($c);
        }

        /**
         * @expectedException        Exception
         * @expectedExceptionMessage Could not write attachments. Your directory may be unwritable by PHP.
         */
        public function testSaveAttachmentsWithoutPermissions()
        {
            $mid = 'm0001';
            $file = __DIR__.'/mails/'.$mid;
            $attach_dir = __DIR__.'/mails/attach_'.$mid.'/';
            $attach_url = 'http://www.company.com/attachments/'.$mid.'/';

            $Parser = new Parser();
            $Parser->setStream(fopen($file, 'r'));

            global $mockFopen;
            $mockFopen = true;

            $Parser->saveAttachments($attach_dir, $attach_url);
        }

        /**
         * @expectedException        Exception
         * @expectedExceptionMessage Could not create file for attachment: duplicate filename.
         */
        public function testSaveAttachmentsWithDuplicateNames()
        {
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

        /**
         * @expectedException        Exception
         * @expectedExceptionMessage Invalid filename strategy argument provided.
         */
        public function testSaveAttachmentsInvalidStrategy()
        {
            $file = __DIR__ . '/mails/m0026';

            $Parser = new Parser();
            $Parser->setText(file_get_contents($file));

            $Parser->saveAttachments('dir', false, 'InvalidValue');
        }
    }
}
