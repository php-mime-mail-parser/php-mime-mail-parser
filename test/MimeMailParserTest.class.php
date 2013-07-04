<?php
/*
* Class Test : MimeMailParserTest
*
* Liste des mails :
* m0001 : mail avec un fichier attaché de 1 ko
* m0002 : mail avec un fichier attaché de 3 ko
* m0003 : mail avec un fichier attaché de 14 ko
* m0004 : mail avec un fichier attaché de 800 ko
* m0005 : mail avec un fichier attaché de 1 500 ko
* m0006 : mail avec un fichier attaché de 3 196 ko
*/
include_once(__DIR__."/../lib/MimeMailParser.class.php");

class MimeMailParserTest extends PHPUnit_Framework_TestCase {
	
	
	/**
	* @dataProvider provideMails
	*/
	function testgetAttachments($mid, $nbAttachments){
		//Import test file
		$file = __DIR__."/mails/".$mid;
		$fd = fopen($file, "r");
		$contents = fread($fd, filesize($file));
		fclose($fd);

		$Parser = new MimeMailParser();
		$Parser->setText($contents);

		$attachments = $Parser->getAttachments();

		$this->assertEquals(count($attachments),$nbAttachments);
	}

	function provideMails(){
		$mails = array(
			array('m0001',1),
			array('m0002',1),
			array('m0003',1),
			array('m0004',1),
			array('m0005',1),
			array('m0006',1)
		);
		return $mails;
	}
}
?>

