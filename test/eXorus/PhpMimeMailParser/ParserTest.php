<?php

namespace Test\eXorus\PhpMimeMailParser;

use eXorus\PhpMimeMailParser\Parser;
use eXorus\PhpMimeMailParser\Attachment;
use eXorus\PhpMimeMailParser\Exception;

require_once APP_SRC . 'Parser.php';

/*
* Class Test : MimeMailParserTest
*/
class ParserTest extends \PHPUnit_Framework_TestCase {

	function provideData(){

		$data = array(
			array(
				'm0001',
				'Mail avec fichier attaché de 1ko',
				'Name <name@company.com>',
				'name@company2.com',
				array('MATCH',"\n"),
				array('COUNT',1,'<div dir="ltr"><br></div>'),
				array(array('attach01',2,'a',1)),
				0),
			array(
				'm0002',
				'Mail avec fichier attaché de 3ko',
				'Name <name@company.com>',
				'name@company2.com',
				array('MATCH',"\n"),
				array('COUNT',1,'<div dir="ltr"><br></div>'),
				array(array('attach02',2229,'Lorem ipsum',8)),
				0),
			array(
				'm0003',
				'Mail de 14 Ko',
				'Name <name@company.com>',
				'name@company2.com',
				array('MATCH',"\n"),
				array('COUNT',1,'<div dir="ltr"><br></div>'),
				array(array('attach03',13369,'dolor sit amet',48)),
				0),
			array(
				'm0004',
				'Mail de 800ko',
				'Name <name@company.com>',
				'name@company2.com',
				array('MATCH',"\n"),
				array('COUNT',1,'<div dir="ltr"><br></div>'),
				array(array('attach04',817938,'Phasellus scelerisque',242)),
				0),
			array(
				'm0005',
				'Mail de 1500 Ko',
				'Name <name@company.com>',
				'name@company2.com',
				array('MATCH',"\n"),
				array('COUNT',1,'<div dir="ltr"><br></div>'),
				array(array('attach05',1635877,'Aenean ultrices',484)),
				0),
			array(
				'm0006',
				'Mail de 3 196 Ko',
				'Name <name@company.com>',
				'name@company2.com',
				array('MATCH',"\n"),
				array('COUNT',1,'<div dir="ltr"><br></div>'),
				array(array('attach06',3271754,'lectus ac leo ullamcorper',968)),
				0),
			array(
				'm0007',
				'Mail avec fichier attaché de 3ko',
				'Name <name@company.com>',
				'name@company2.com',
				array('MATCH',"\n"),
				array('COUNT',1,'<div dir="ltr"><br></div>'),
				array(array('attach02',2229,'facilisis',4)),
				0),
			array(
				'm0008',
				'Testing MIME E-mail composing with cid',
				'Name <name@company.com>',
				'Name <name@company2.com>',
				array('COUNT',1,'Please use an HTML capable mail program to read'),
				array('COUNT',1,'<center><h1>Testing MIME E-mail composing with cid</h1></center>'),
				array(array('logo.jpg',2695,'',0),array('background.jpg',18255,'',0),array('attachment.txt',2229,'Sed pulvinar',4)),
				2),
			array(
				'm0009',
				'Ogone NIEUWE order Maurits PAYID: 951597484 / orderID: 456123 / status: 5',
				'"Ogone" <noreply@ogone.com>',
				'info@testsite.com',
				array('COUNT',1,'951597484'),
				array('MATCH',''),
				array(),
				0),
			array(
				'm0010',
				'Mail de 800ko without filename',
				'Name <name@company.com>',
				'name@company2.com',
				array('MATCH',"\n"),
				array('COUNT',1,'<div dir="ltr"><br></div>'),
				array(array('noname1',817938,'Suspendisse',726)),
				0),
			array(
				'm0011',
				'Hello World !',
				'Name <name@company.com>',
				'Name <name@company.com>',
				array('COUNT',1,'This is a text body'),
				array('MATCH',''),
				array(array('file.txt',29,'This is a file',1)),
				0),
			array(
				'm0012',
				'Hello World !',
				'Name <name@company.com>',
				'Name <name@company.com>',
				array('COUNT',1,'This is a text body'),
				array('MATCH',''),
				array(array('file.txt',29,'This is a file',1)),
				0),
			array(
				'm0013',
				'50032266 CAR 11_MNPA00A01_9PTX_H00 ATT N° 1467829. pdf',
				'NAME Firstname <firstname.name@groupe-company.com>',
				'"paul.dupont@company.com" <paul.dupont@company.com>',
				array('COUNT',1,'Superviseur de voitures'),
				array('MATCH',''),
				array(array('50032266 CAR 11_MNPA00A01_9PTX_H00 ATT N° 1467829.pdf',10,'',0)),
				0),
			array(
				'm0014',
				'Test message from Netscape Communicator 4.7',
				'Doug Sauder <dwsauder@example.com>',
				'Joe Blow <blow@example.com>',
				array('COUNT',1,'Die Hasen und die'),
				array('MATCH',''),
				array(array('HasenundFrösche.txt',747,'noch',2)),
				0),
			array(
				'm0015',
				'Up to $30 Off Multivitamins!',
				'"Vitamart.ca" <service@vitamart.ca>',
				'me@somewhere.com',
				array('COUNT',1,'Hi,'),
				array('COUNT',1,'<strong>*How The Sale Works</strong>'),
				array(array('noname1',2616,'$150+ of Multivitamins',1),array('noname2',17341,'div',82)),
				0)
		);
		return $data;
	}

    /**
     * @dataProvider provideData
     */
    public function testFromPath($mid,$subjectExpected,$fromExpected,$toExpected,$textExpected,$htmlExpected,$attachmentsExpected,$countEmbeddedExpected)
    {
    	//Init
    	$file = __DIR__.'/mails/'.$mid;
		$attach_dir = __DIR__.'/mails/attach_'.$mid.'/';
		$attach_url = 'http://www.company.com/attachments/'.$mid.'/';

		//Load From Path
		$Parser = new Parser();
		$Parser->setPath($file);

		//Test Header : subject
		$this->assertEquals($subjectExpected,$Parser->getHeader('subject'));

		//Test Header : from
		$this->assertEquals($fromExpected,$Parser->getHeader('from'));

		//Test Header : to
		$this->assertEquals($toExpected,$Parser->getHeader('to'));

		//Test Invalid Header
		$this->assertFalse($Parser->getHeader('azerty'));

		//Test  Body : text
		if($textExpected[0] == 'COUNT'){
			$this->assertEquals($textExpected[1],substr_count($Parser->getMessageBody('text'),$textExpected[2]));
		}
		elseif($textExpected[0] == 'MATCH'){
			$this->assertEquals($textExpected[1],$Parser->getMessageBody('text'));
		}

		//Test Body : html
		if($htmlExpected[0] == 'COUNT'){
			$this->assertEquals($htmlExpected[1],substr_count($Parser->getMessageBody('html'),$htmlExpected[2]));
		}
		elseif($htmlExpected[0] == 'MATCH'){
			$this->assertEquals($htmlExpected[1],$Parser->getMessageBody('html'));
		}

		//Test Nb Attachments
		$attachments = $Parser->getAttachments();
		$this->assertEquals(count($attachmentsExpected),count($attachments));
		$iterAttachments = 0;

		//Test Attachments
		if(count($attachmentsExpected) > 0){

			//Save attachments
			$Parser->saveAttachments($attach_dir, $attach_url);

			foreach($attachmentsExpected as $attachmentExpected){

				//Test Exist Attachment
				$this->assertTrue(file_exists($attach_dir.$attachmentExpected[0]));

				//Test Filename Attachment
				$this->assertEquals($attachmentExpected[0],$attachments[$iterAttachments]->getFilename());

				//Test Size Attachment
				$this->assertEquals($attachmentExpected[1],filesize($attach_dir.$attachments[$iterAttachments]->getFilename()));

				//Test Inside Attachment
				if($attachmentExpected[2] != '' && $attachmentExpected[3] >0){
				
					$fileContent = file_get_contents($attach_dir.$attachments[$iterAttachments]->getFilename(), FILE_USE_INCLUDE_PATH);
					$this->assertEquals($attachmentExpected[3],substr_count($fileContent, $attachmentExpected[2]));
				}

				//Test 
				if($countEmbeddedExpected > 0){

					$htmlEmbedded = $Parser->getMessageBody('html', TRUE);
					$this->assertEquals($countEmbeddedExpected,substr_count($htmlEmbedded, $attach_url));
				}
				
				//Remove Attachment
				unlink($attach_dir.$attachments[$iterAttachments]->getFilename());

				$iterAttachments++;

			}
			//Remove Attachment Directory
			rmdir($attach_dir);
		}
    }

    /**
     * @dataProvider provideData
     */
    public function testFromText($mid,$subjectExpected,$fromExpected,$toExpected,$textExpected,$htmlExpected,$attachmentsExpected,$countEmbeddedExpected)
    {
    	//Init
		$file = __DIR__.'/mails/'.$mid;
		$attach_dir = __DIR__.'/mails/attach_'.$mid.'/';
		$attach_url = 'http://www.company.com/attachments/'.$mid.'/';

		//Load From Text
		$Parser = new Parser();
		$Parser->setText(file_get_contents($file));

		//Test Header : subject
		$this->assertEquals($subjectExpected,$Parser->getHeader('subject'));

		//Test Header : from
		$this->assertEquals($fromExpected,$Parser->getHeader('from'));

		//Test Header : to
		$this->assertEquals($toExpected,$Parser->getHeader('to'));

		//Test Invalid Header
		$this->assertFalse($Parser->getHeader('azerty'));

		//Test  Body : text
		if($textExpected[0] == 'COUNT'){
			$this->assertEquals($textExpected[1],substr_count($Parser->getMessageBody('text'),$textExpected[2]));
		}
		elseif($textExpected[0] == 'MATCH'){
			$this->assertEquals($textExpected[1],$Parser->getMessageBody('text'));	
		}

		//Test Body : html
		if($htmlExpected[0] == 'COUNT'){
			$this->assertEquals($htmlExpected[1],substr_count($Parser->getMessageBody('html'),$htmlExpected[2]));
		}
		elseif($htmlExpected[0] == 'MATCH'){
			$this->assertEquals($htmlExpected[1],$Parser->getMessageBody('html'));	
		}

		//Test Nb Attachments
		$attachments = $Parser->getAttachments();
		$this->assertEquals(count($attachmentsExpected),count($attachments));
		$iterAttachments = 0;

		//Test Attachments
		if(count($attachmentsExpected) > 0){

			//Save attachments
			$Parser->saveAttachments($attach_dir, $attach_url);

			foreach($attachmentsExpected as $attachmentExpected){

				//Test Exist Attachment
				$this->assertTrue(file_exists($attach_dir.$attachmentExpected[0]));

				//Test Filename Attachment
				$this->assertEquals($attachmentExpected[0],$attachments[$iterAttachments]->getFilename());

				//Test Size Attachment
				$this->assertEquals($attachmentExpected[1],filesize($attach_dir.$attachments[$iterAttachments]->getFilename()));

				//Test Inside Attachment
				if($attachmentExpected[2] != '' && $attachmentExpected[3] >0){
				
					$fileContent = file_get_contents($attach_dir.$attachments[$iterAttachments]->getFilename(), FILE_USE_INCLUDE_PATH);
					$this->assertEquals($attachmentExpected[3],substr_count($fileContent, $attachmentExpected[2]));
				}

				//Test 
				if($countEmbeddedExpected > 0){

					$htmlEmbedded = $Parser->getMessageBody('html', TRUE);
					$this->assertEquals($countEmbeddedExpected,substr_count($htmlEmbedded, $attach_url));
				}
				
				//Remove Attachment
				unlink($attach_dir.$attachments[$iterAttachments]->getFilename());

				$iterAttachments++;

			}
			//Remove Attachment Directory
			rmdir($attach_dir);
		}
	}


    /**
     * @dataProvider provideData
     */
    public function testFromStream($mid,$subjectExpected,$fromExpected,$toExpected,$textExpected,$htmlExpected,$attachmentsExpected,$countEmbeddedExpected)
    {
    	//Init
    	$file = __DIR__.'/mails/'.$mid;
		$attach_dir = __DIR__.'/mails/attach_'.$mid.'/';
		$attach_url = 'http://www.company.com/attachments/'.$mid.'/';

		//Load From Path
		$Parser = new Parser();
		$Parser->setStream(fopen($file, 'r'));

		//Test Header : subject
		$this->assertEquals($subjectExpected,$Parser->getHeader('subject'));

		//Test Header : from
		$this->assertEquals($fromExpected,$Parser->getHeader('from'));

		//Test Header : to
		$this->assertEquals($toExpected,$Parser->getHeader('to'));

		//Test Invalid Header
		$this->assertFalse($Parser->getHeader('azerty'));
		
		//Test  Body : text
		if($textExpected[0] == 'COUNT'){
			$this->assertEquals($textExpected[1],substr_count($Parser->getMessageBody('text'),$textExpected[2]));
		}
		elseif($textExpected[0] == 'MATCH'){
			$this->assertEquals($textExpected[1],$Parser->getMessageBody('text'));
		}

		//Test Body : html
		if($htmlExpected[0] == 'COUNT'){
			$this->assertEquals($htmlExpected[1],substr_count($Parser->getMessageBody('html'),$htmlExpected[2]));
		}
		elseif($htmlExpected[0] == 'MATCH'){
			$this->assertEquals($htmlExpected[1],$Parser->getMessageBody('html'));
		}

		//Test Nb Attachments
		$attachments = $Parser->getAttachments();
		$this->assertEquals(count($attachmentsExpected),count($attachments));
		$iterAttachments = 0;

		//Test Attachments
		if(count($attachmentsExpected) > 0){

			//Save attachments
			$Parser->saveAttachments($attach_dir, $attach_url);

			foreach($attachmentsExpected as $attachmentExpected){

				//Test Exist Attachment
				$this->assertTrue(file_exists($attach_dir.$attachmentExpected[0]));

				//Test Filename Attachment
				$this->assertEquals($attachmentExpected[0],$attachments[$iterAttachments]->getFilename());

				//Test Size Attachment
				$this->assertEquals($attachmentExpected[1],filesize($attach_dir.$attachments[$iterAttachments]->getFilename()));

				//Test Inside Attachment
				if($attachmentExpected[2] != '' && $attachmentExpected[3] >0){
				
					$fileContent = file_get_contents($attach_dir.$attachments[$iterAttachments]->getFilename(), FILE_USE_INCLUDE_PATH);
					$this->assertEquals($attachmentExpected[3],substr_count($fileContent, $attachmentExpected[2]));
				}

				//Test 
				if($countEmbeddedExpected > 0){

					$htmlEmbedded = $Parser->getMessageBody('html', TRUE);
					$this->assertEquals($countEmbeddedExpected,substr_count($htmlEmbedded, $attach_url));
				}
				
				//Remove Attachment
				unlink($attach_dir.$attachments[$iterAttachments]->getFilename());

				$iterAttachments++;

			}
			//Remove Attachment Directory
			rmdir($attach_dir);
		}
    }

}
?>

