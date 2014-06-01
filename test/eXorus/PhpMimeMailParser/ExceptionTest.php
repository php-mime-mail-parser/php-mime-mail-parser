<?php

namespace Test\eXorus\PhpMimeMailParser;

use eXorus\PhpMimeMailParser\Parser;
use eXorus\PhpMimeMailParser\Attachment;
use eXorus\PhpMimeMailParser\Exception;

class ExceptionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException        Exception
     * @expectedExceptionMessage setPath() or setText() or setStream() must be called before retrieving email headers.
     */
    public function testGetHeader()
    {
		$Parser = new Parser();
		$Parser->getHeader('test');
    }

    /**
     * @expectedException        Exception
     * @expectedExceptionMessage Invalid type specified for getMessageBody(). "type" can either be text or html.
     */
    public function testgetMessageBody()
    {
		$Parser = new Parser();
		$Parser->getMessageBody('azerty');
    }

    /**
     * @expectedException        Exception
     * @expectedExceptionMessage setStream() expects parameter stream to be resource.
     */
    public function testSetStream()
    {
        $Parser = new Parser();
        $Parser->setStream('azerty');
    }

    /**
     * @expectedException        Exception
     * @expectedExceptionMessage setStream() expects parameter stream to be stream resource.
     */
    public function testSetStreamResource()
    {
        $c = mysql_connect();
        $Parser = new Parser();
        $Parser->setStream($c);
    }
}
?>