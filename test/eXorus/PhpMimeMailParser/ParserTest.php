<?php

namespace Test\eXorus\PhpMimeMailParser;

use eXorus\PhpMimeMailParser\Parser;
use eXorus\PhpMimeMailParser\Attachment;

require_once APP_SRC . 'Parser.php';

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
* m0007 : mail avec un fichier attaché sans content-disposition
* m0008 : mail avec des fichiers attachés avec content-id
* m0009 : testWithoutCharset [Issue 7]
* m0010 : mail de 800ko without filename
* m0011 : mail contains text body and after an attached text (text/plain) [Issue 11]
* m0012 : mail contains text body and before an attached text (text/plain) [Issue 11]
* m0013 : attachment name not correctly decoded [Issue 13]
*/

class ParserTest extends \PHPUnit_Framework_TestCase {
	
	/**
	* @dataProvider provideMails
	*/
	function testGetAttachmentsWithText($mid, $nbAttachments, $size, $subject, $filename){
				
		$file = __DIR__."/mails/".$mid;
		$fd = fopen($file, "r");
		$contents = fread($fd, filesize($file));
		fclose($fd);

		$Parser = new Parser();
		$Parser->setText($contents);

		$this->assertEquals($subject,$Parser->getHeader('subject'));

		$attachments = $Parser->getAttachments();

		$this->assertEquals($nbAttachments,count($attachments));
		
		if($size != NULL){
			$attach_dir = __DIR__."/mails/attach_".$mid."/";
			$Parser->saveAttachments($attach_dir, "");

			$this->assertEquals($size,filesize($attach_dir.$attachments[0]->getFilename()));
			unlink($attach_dir.$attachments[0]->getFilename());

			$this->assertEquals($filename,$attachments[0]->getFilename());

			rmdir($attach_dir);
		}
	}

	/**
	* @dataProvider provideMails
	*/
	function testGetAttachmentsWithPath($mid, $nbAttachments, $size, $subject, $filename){

		$file = __DIR__."/mails/".$mid;

		$Parser = new Parser();
		$Parser->setPath($file);

		$this->assertEquals($subject,$Parser->getHeader('subject'));

		$attachments = $Parser->getAttachments();

		$this->assertEquals($nbAttachments,count($attachments));

		if($size != NULL){
			$attach_dir = __DIR__."/mails/attach_".$mid."/";
			$Parser->saveAttachments($attach_dir, "");

			$this->assertEquals($size,filesize($attach_dir.$attachments[0]->getFilename()));
			unlink($attach_dir.$attachments[0]->getFilename());

			$this->assertEquals($filename,$attachments[0]->getFilename());

			rmdir($attach_dir);
		}
	}

	function provideMails(){
		// mid, nbAttachments, size, subject, filename
		$mails = array(
			array('m0001',1,2, 'Mail avec fichier attaché de 1ko', 'attach01'),
			array('m0002',1,2229, 'Mail avec fichier attaché de 3ko', 'attach02'),
			array('m0003',1,13369, 'Mail de 14 Ko', 'attach03'),
			array('m0004',1,817938, 'Mail de 800ko', 'attach04'),
			array('m0005',1,1635877, 'Mail de 1500 Ko', 'attach05'),
			array('m0006',1,3271754, 'Mail de 3 196 Ko', 'attach06'),
			array('m0007',1,2229, 'Mail avec fichier attaché de 3ko', 'attach02'),
			array('m0008',3,NULL, 'Testing MIME E-mail composing with cid', ''),
			array('m0010',1,817938, 'Mail de 800ko without filename', 'noname1'),
			array('m0013',1,10, '50032266 CAR 11_MNPA00A01_9PTX_H00 ATT N° 1467829. pdf', '50032266 CAR 11_MNPA00A01_9PTX_H00 ATT N° 1467829.pdf')
		);
		return $mails;
	}

	function testGetAttachmentsWithContentId(){
		$mid = "m0008";

		$file = __DIR__."/mails/".$mid;

		$Parser = new Parser();
		$Parser->setPath($file);

		$attach_dir = __DIR__."/mails/attach_".$mid."/";
		$attach_url = "http://www.company.com/attachments/".$mid."/";
		$Parser->saveAttachments($attach_dir, $attach_url);

		$html_embedded = $Parser->getMessageBody('html', TRUE);

		$this->assertEquals(2,substr_count($html_embedded, $attach_url));
		unlink($attach_dir.'attachment.txt');
		unlink($attach_dir.'background.jpg');
		unlink($attach_dir.'logo.jpg');
		rmdir($attach_dir);
	}
	
	function testWithoutCharset(){
		// Issue 7
		$mid = "m0009";
		$file = __DIR__."/mails/".$mid;
		$Parser = new Parser();
		$Parser->setPath($file);
		$Parser->getMessageBody('text');
		$Parser->getMessageBody('html');
	}

	function provideMailsForIssue11(){
		$mails = array(
			array('m0011'),
			array('m0012')
		);
		return $mails;
	}


	/**
	* @dataProvider provideMailsForIssue11
	*/
	function testGetMessageBody($mid){
		// Issue 11
		$file = __DIR__."/mails/".$mid;
		$Parser = new Parser();
		$Parser->setPath($file);

		$textBody = $Parser->getMessageBody('text');
		$this->assertEquals(1,substr_count($textBody, "This is a text body"));

		$htmlBody = $Parser->getMessageBody('html');
		$this->assertEquals("",$htmlBody);

		$attach_dir = __DIR__."/mails/attach_".$mid."/";
		$Parser->saveAttachments($attach_dir, "");
		$fileBody = file_get_contents($attach_dir.'file.txt', FILE_USE_INCLUDE_PATH);
		$this->assertEquals(1,substr_count($fileBody, "This is a file"));
		unlink($attach_dir.'file.txt');
		rmdir($attach_dir);

	}

}
?>

