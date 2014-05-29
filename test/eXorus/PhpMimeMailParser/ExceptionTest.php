<?php

namespace Test\eXorus\PhpMimeMailParser;

use eXorus\PhpMimeMailParser\Parser;
use eXorus\PhpMimeMailParser\Attachment;
use eXorus\PhpMimeMailParser\Exception;

class ExceptionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException        Exception
     * @expectedExceptionMessage MimeMailParser::setPath() or MimeMailParser::setText() must be called before retrieving email headers.
     */
    public function testGetHeader()
    {
		$Parser = new Parser();
		$Parser->getHeader('test');
    }

    /**
     * @expectedException        Exception
     * @expectedExceptionMessage Invalid type specified for MimeMailParser::getMessageBody. "type" can either be text or html.
     */
    public function testgetMessageBody()
    {
		$Parser = new Parser();
		$Parser->getMessageBody('azerty');
    }
}
?>