<?php
namespace Tests\PhpMimeMailParser;

use PhpMimeMailParser\Attachment;
use PhpMimeMailParser\Contracts\CharsetManager;
use PhpMimeMailParser\MiddleWare;
use PhpMimeMailParser\MiddleWareStack;
use PhpMimeMailParser\MimePart;
use PhpMimeMailParser\Parser;

/**
 * @covers \PhpMimeMailParser\Parser
 * @covers \PhpMimeMailParser\MimePart
 * @covers \PhpMimeMailParser\Attachment
 * @covers \PhpMimeMailParser\MiddleWareStack
 * @covers \PhpMimeMailParser\Middleware
 */
final class ParserTest extends TestCase
{
    /**
     * @dataProvider provideData
     */
    public function testInlineAttachmentsFalse(
        $mid,
        $subjectExpected,
        $fromAddressesExpected,
        $fromExpected,
        $toAddressesExpected,
        $toExpected,
        $textExpected,
        $htmlExpected,
        $attachmentsExpected,
        $countEmbeddedExpected
    ) {
        //Init
        $file = __DIR__.'/mails/'.$mid;

        //Load From Path
        $Parser = new Parser();
        $Parser->setPath($file);

        // Remove inline attachments from array
        $attachmentsExpected = array_filter($attachmentsExpected, function ($attachment) {
            return ($attachment[5] != 'inline');
        });

        //Test Nb Attachments (ignoring inline attachments)
        $attachments = $Parser->getNestedAttachments2(['attachment']);
        $this->assertEquals(count($attachmentsExpected), count($attachments));
        $iterAttachments = 0;

        //Test Attachments
        if (count($attachmentsExpected) > 0) {
            foreach ($attachmentsExpected as $attachmentExpected) {
                //Test Filename Attachment
                $this->assertEquals($attachmentExpected[0], $attachments[$iterAttachments]->getFilename());

                //Test ContentType Attachment
                $this->assertEquals($attachmentExpected[4], $attachments[$iterAttachments]->getContentType());

                //Test ContentDisposition Attachment
                $this->assertEquals($attachmentExpected[5], $attachments[$iterAttachments]->getContentDisposition());

                //Test md5 of Headers Attachment
                $this->assertEquals(
                    $attachmentExpected[6],
                    md5(serialize($attachments[$iterAttachments]->getHeaders()))
                );

                $this->assertIsResource($attachments[$iterAttachments]->getStream());

                $iterAttachments++;
            }
        }
    }

    /**
     * test for being able to extract multiple inline text/plain & text/html parts
     * related to issue #163
     */
    public function testMultiPartInline()
    {
        $file = __DIR__ .'/mails/issue163';
        $Parser = new Parser();
        $Parser->setText(file_get_contents($file));
        $inline_parts = $Parser->getInlineParts('text');
        $this->assertEquals(is_array($inline_parts), true);
        $this->assertEquals(count($inline_parts), 2);
        $this->assertEquals($inline_parts[0], "First we have a text block, then we insert an image:\r\n\r\n");
        $this->assertEquals($inline_parts[1], "\r\n\r\nThen we have more text\r\n\r\n-- sent from my phone.");
    }

    public function testIlligalAttachmentFilenameForDispositionFilename()
    {
        $file = __DIR__ . '/mails/issue133';
        $Parser = new Parser();
        $Parser->setText(file_get_contents($file));
        $attachments = $Parser->getNestedAttachments2(['attachment']);

        $this->assertEquals("attach_01", $attachments[0]->getFilename());
    }

    public function testCharsetMacCyrillic()
    {
        $file = __DIR__ . '/mails/issue230';
        $Parser = new Parser();
        $Parser->setText(file_get_contents($file));

        $this->assertEquals('Scientific conferences, Bulgaria 2019', $Parser->getHeader('subject'));
        $body = $Parser->getMessageBody('text');
        $this->assertEquals(is_string($body), true);
    }

    /**
     * Test for being able to extract a text/plain part from an email with 10 attachments.
     * Related to pr #172.
     *
     * @return void
     */
    public function testMultiPartWithAttachments()
    {
        $file = __DIR__ .'/mails/m0028';
        $Parser = new Parser();
        $Parser->setText(file_get_contents($file));
        $body = $Parser->getMessageBody('text');
        $this->assertEquals(is_string($body), true);
        $this->assertEquals($body, "This is the plain text content of the email");
    }

    public function testIlligalAttachmentFilenameForContentName()
    {
        $file = __DIR__ . '/mails/m0027';
        $Parser = new Parser();
        $Parser->setText(file_get_contents($file));
        $attachments = $Parser->getNestedAttachments2(['attachment']);

        $this->assertEquals("1234_.._.._1234.txt", $attachments[0]->getFilename());
    }

    public function testAttachmentFilenameWithLineBreaksForContentName()
    {
        $file = __DIR__ . '/mails/issue250';
        $Parser = new Parser();
        $Parser->setText(file_get_contents($file));
        $attachments = $Parser->getNestedAttachments2(['attachment']);
        $this->assertEquals("Kontoutskrift for 1506.14.90466_Bedriftskonto.pdf", $attachments[0]->getFilename());
    }

    public function testAttachmentsWithDuplicatesSuffix()
    {
        $file = __DIR__ . '/mails/m0026';
        $Parser = new Parser();
        $Parser->setText(file_get_contents($file));

        $attachDir = $this->tempdir('m0002_attachments');
        $Parser->saveAttachments($attachDir, false);

        $attachmentFiles = glob($attachDir . '*');

        // Default: generate filename suffix, so we should have two files
        $this->assertCount(2, $attachmentFiles);
        $this->assertEquals($attachDir . 'ATT00001.txt', $attachmentFiles[0]);
        $this->assertEquals($attachDir . 'ATT00001_1.txt', $attachmentFiles[1]);
    }

    public function testAttachmentsWithDuplicatesRandom()
    {
        $file = __DIR__ . '/mails/m0026';
        $Parser = new Parser();
        $Parser->setText(file_get_contents($file));

        $attachDir = $this->tempdir('m0026_attachments');
        $Parser->saveAttachments($attachDir, false, Parser::ATTACHMENT_RANDOM_FILENAME);

        $attachmentFiles = glob($attachDir . '*');

        // Default: generate random filename, so we should have two files
        $this->assertCount(2, $attachmentFiles);
    }

    public function testMultipleContentTransferEncodingHeader()
    {
        $file = __DIR__.'/mails/issue126';
        $Parser = new Parser();
        $Parser->setText(file_get_contents($file));
        $Parser->getMessageBody('text');
        $Parser->getAttachments2();
        $this->assertTrue(true);
    }

    public function testCreatingMoreThanOneInstanceOfParser()
    {
        $file = __DIR__.'/mails/issue84';
        (new Parser())->setPath($file)->getMessageBody();
        (new Parser())->setPath($file)->getMessageBody();
        $this->assertTrue(true);
    }

    public function testDecodeCharsetFailedIsIgnored()
    {
        $file = __DIR__ . '/mails/issue116';
        $Parser = new Parser();
        $Parser->setText(file_get_contents($file));
        $this->assertEquals("ЖД№41 от 28.09.2016", $Parser->getHeader('subject'));
    }

    public function testEmbeddedDataReturnTheFirstContentWhenContentIdIsNotUnique()
    {
        $file = __DIR__ . '/mails/issue115';
        $Parser = new Parser();
        $Parser->setText(file_get_contents($file));
        $this->assertEquals(1, substr_count($Parser->getMessageBody('htmlEmbedded'), 'image/'));
    }

    public function testGetAddressesWithQuot()
    {
        $file = __DIR__ . '/mails/m0124';
        $Parser = new Parser();
        $Parser->setText(file_get_contents($file));
        $from = $Parser->getAddresses('from');
        $this->assertEquals([
            [
                "display" => 'ФГУП "СибНИА им.С.А.Чаплыгина""',
                "address" => 'user@domain.ru',
                "is_group" => false
            ]
            ], $from);
    }

    public function testGetMessageBodyNotFound()
    {
        $file = __DIR__ . '/mails/m0124';
        $Parser = new Parser();
        $Parser->setText(file_get_contents($file));
        $body = $Parser->getMessageBody();
        $this->assertEmpty($body);
    }

    public function testUnknownContentDisposition()
    {
        $file = __DIR__ . '/mails/issue182';
        $Parser = new Parser();
        $Parser->setText(file_get_contents($file));
        
        $this->assertCount(1, $Parser->getAttachments2());
        $this->assertCount(1, $Parser->getNestedAttachments2(['inline', 'attachment']));
    }

    public function provideData()
    {
        $data = [
            /*
            array(
                // Mail ID
                'm0001',
                // Subject Expected
                'Mail avec fichier attaché de 1ko',
                // From Expected (array)
                array(array('display' => 'Name','address' => 'name@company.com','is_group' => false)),
                // From Expected
                'Name <name@company.com>',
                // To Expected (array)
                array(array('display' => 'Name','address' => 'name@company.com','is_group' => false)),
                // To Expected
                'name@company2.com',
                // Text Expected (MATCH = exact match, COUNT = Count the number of substring occurrences )
                array('MATCH',"\n"),
                // Html Expected (MATCH = exact match, COUNT = Count the number of substring occurrences )
                array('COUNT',1,'<div dir="ltr"><br></div>'),
                // Array of attachments (FileName, File Size, String inside the file,
                //      Count of this string, ContentType, MD5 of Serialize Headers, String inside the HTML Embedded)
                array(array('attach01',2,'a',1,'image/gif','attachment', '4c1d5793', 'b3309f')),
                // Count of Embedded Attachments
                0)
            */
                [
                    'm0001',
                    'Mail avec fichier attaché de 1ko',
                    [
                        [
                            'display' => 'Name',
                            'address' => 'name@company.com',
                            'is_group' => false
                            ]
                    ],
                    'Name <name@company.com>',
                    [
                        [
                            'display' => 'name@company2.com',
                            'address' => 'name@company2.com',
                            'is_group' => false
                            ]
                    ],
                    'name@company2.com',
                    ['MATCH',"\n"],
                    ['COUNT',1,'<div dir="ltr"><br></div>'],
                    [
                        [
                            'attach01',
                            2,
                            'a',
                            1,
                            'application/octet-stream',
                            'attachment',
                            '04c1d5793efa97c956d011a8b3309f05',
                            '',
                            ]
                        ],
                    0],
                [
                    'm0002',
                    'Mail avec fichier attaché de 3ko',
                    [
                        [
                            'display' => 'Name',
                            'address' => 'name@company.com',
                            'is_group' => false
                            ]
                    ],
                    'Name <name@company.com>',
                    [
                        [
                            'display' => 'name@company2.com',
                            'address' => 'name@company2.com',
                            'is_group' => false
                            ]
                    ],
                    'name@company2.com',
                    ['MATCH',"\n"],
                    ['COUNT',1,'<div dir="ltr"><br></div>'],
                    [
                        [
                            'attach02',
                            2229,
                            'Lorem ipsum',
                            8,
                            'application/octet-stream',
                            'attachment',
                            '18f541cc6bf49209d2bf327ecb887355',
                            '',
                            ]
                        ],
                    0],
                [
                    'm0003',
                    'Mail de 14 Ko',
                    [
                        [
                            'display' => 'Name',
                            'address' => 'name@company.com',
                            'is_group' => false
                            ]
                    ],
                    'Name <name@company.com>',
                    [
                        [
                            'display' => 'name@company2.com',
                            'address' => 'name@company2.com',
                            'is_group' => false
                            ]
                    ],
                    'name@company2.com',
                    ['MATCH',"\n"],
                    ['COUNT',1,'<div dir="ltr"><br></div>'],
                    [
                        [
                            'attach03',
                            13369,
                            'dolor sit amet',
                            48,
                            'application/octet-stream',
                            'attachment',
                            '8734417734fabfa783df6fed0ccf7a4a',
                            '',
                            ]
                        ],
                    0],
                [
                    'm0004',
                    'Mail de 800ko',
                    [
                        [
                            'display' => 'Name',
                            'address' => 'name@company.com',
                            'is_group' => false
                            ]
                    ],
                    'Name <name@company.com>',
                    [
                        [
                            'display' => 'name@company2.com',
                            'address' => 'name@company2.com',
                            'is_group' => false
                            ]
                    ],
                    'name@company2.com',
                    ['MATCH',"\n"],
                    ['COUNT',1,'<div dir="ltr"><br></div>'],
                    [
                        [
                            'attach04',
                            817938,
                            'Phasellus scelerisque',
                            242,
                            'application/octet-stream',
                            'attachment',
                            'c0b5348ef825bf62ba2d07d70d4b9560',
                            '',
                            ]
                        ],
                    0],
                [
                    'm0005',
                    'Mail de 1500 Ko',
                    [
                        [
                            'display' => 'Name',
                            'address' => 'name@company.com',
                            'is_group' => false
                            ]
                    ],
                    'Name <name@company.com>',
                    [
                        [
                            'display' => 'name@company2.com',
                            'address' => 'name@company2.com',
                            'is_group' => false
                            ]
                    ],
                    'name@company2.com',
                    ['MATCH',"\n"],
                    ['COUNT',1,'<div dir="ltr"><br></div>'],
                    [
                        [
                            'attach05',
                            1635877,
                            'Aenean ultrices',
                            484,
                            'application/octet-stream',
                            'attachment',
                            '1ced323befc39ebbc147e7588d11ab08',
                            '',
                            ]
                        ],
                    0],
                [
                    'm0006',
                    'Mail de 3 196 Ko',
                    [
                        [
                            'display' => 'Name',
                            'address' => 'name@company.com',
                            'is_group' => false
                            ]
                    ],
                    'Name <name@company.com>',
                    [
                        [
                            'display' => 'name@company2.com',
                            'address' => 'name@company2.com',
                            'is_group' => false
                            ]
                    ],
                    'name@company2.com',
                    ['MATCH',"\n"],
                    ['COUNT',1,'<div dir="ltr"><br></div>'],
                    [
                        [
                            'attach06',
                            3271754,
                            'lectus ac leo ullamcorper',
                            968,
                            'application/octet-stream',
                            'attachment',
                            '5dc6470ab63e86e8f68d88afb11556fe',
                            '',
                            ]
                        ],
                    0],
                [
                    'm0007',
                    'Mail avec fichier attaché de 3ko',
                    [
                        [
                            'display' => 'Name',
                            'address' => 'name@company.com',
                            'is_group' => false
                            ]
                    ],
                    'Name <name@company.com>',
                    [
                        [
                            'display' => 'name@company2.com',
                            'address' => 'name@company2.com',
                            'is_group' => false
                            ]
                    ],
                    'name@company2.com',
                    ['MATCH',"\n"],
                    ['COUNT',1,'<div dir="ltr"><br></div>'],
                    [
                        [
                            'attach02',
                            2229,
                            'facilisis',
                            4,
                            'application/octet-stream',
                            null,
                            '0e6d510323b009da939070faf72e521c',
                            '',
                            ]
                        ],
                    0],
                [
                    'm0008',
                    'Testing MIME E-mail composing with cid',
                    [
                        [
                            'display' => 'Name',
                            'address' => 'name@company.com',
                            'is_group' => false
                            ]
                    ],
                    'Name <name@company.com>',
                    [
                        [
                            'display' => 'Name',
                            'address' => 'name@company2.com',
                            'is_group' => false
                            ]
                    ],
                    'Name <name@company2.com>',
                    ['COUNT',1,'Please use an HTML capable mail program to read'],
                    ['COUNT',1,'<center><h1>Testing MIME E-mail composing with cid</h1></center>'],
                    [
                        [
                            'logo.jpg',
                            2695,
                            '',
                            0,
                            'image/gif',
                            'inline',
                            '102aa12e16635bf2b0b39ef6a91aa95c',
                            '',
                            ],
                        [
                            'background.jpg',
                            18255,
                            '',
                            0,
                            'image/gif',
                            'inline',
                            '798f976a5834019d3f2dd087be5d5796',
                            '',
                            ],
                        [
                            'attachment.txt',
                            2229,
                            'Sed pulvinar',
                            4,
                            'text/plain',
                            'attachment',
                            '71fff85a7960460bdd3c4b8f1ee9279b',
                            '',
                            ]
                        ],
                    2],
                [
                    'm0009',
                    'Ogone NIEUWE order Maurits PAYID: 951597484 / orderID: 456123 / status: 5',
                    [
                        [
                            'display' => 'Ogone',
                            'address' => 'noreply@ogone.com',
                            'is_group' => false
                            ]
                    ],
                    '"Ogone" <noreply@ogone.com>',
                    [
                        [
                            'display' => 'info@testsite.com',
                            'address' => 'info@testsite.com',
                            'is_group' => false
                            ]
                    ],
                    'info@testsite.com',
                    ['COUNT',1,'951597484'],
                    ['MATCH',''],
                    [],
                    0],
                [
                    'm0010',
                    'Mail de 800ko without filename',
                    [
                        [
                            'display' => 'Name',
                            'address' => 'name@company.com',
                            'is_group' => false
                            ]
                    ],
                    'Name <name@company.com>',
                    [
                        [
                            'display' => 'name@company2.com',
                            'address' => 'name@company2.com',
                            'is_group' => false
                            ]
                    ],
                    'name@company2.com',
                    ['MATCH',"\n"],
                    ['COUNT',1,'<div dir="ltr"><br></div>'],
                    [
                        [
                            'noname',
                            817938,
                            'Suspendisse',
                            726,
                            'application/octet-stream',
                            'attachment',
                            '8da4b0177297b1d7f061e44d64cc766f',
                            '',
                            ]
                        ],
                    0],
                [
                    'm0011',
                    'Hello World !',
                    [
                        [
                            'display' => 'Name',
                            'address' => 'name@company.com',
                            'is_group' => false
                            ]
                    ],
                    'Name <name@company.com>',
                    [
                        [
                            'display' => 'Name',
                            'address' => 'name@company.com',
                            'is_group' => false
                            ]
                    ],
                    'Name <name@company.com>',
                    ['COUNT',1,'This is a text body'],
                    ['MATCH',''],
                    [
                        [
                            'file.txt',
                            29,
                            'This is a file',
                            1,
                            'text/plain',
                            'attachment',
                            '839d0486dd1b91e520d456bb17c33148',
                            '',
                            ]
                        ],
                    0],
                [
                    'm0012',
                    'Hello World !',
                    [
                        [
                            'display' => 'Name',
                            'address' => 'name@company.com',
                            'is_group' => false
                            ]
                    ],
                    'Name <name@company.com>',
                    [
                        [
                            'display' => 'Name',
                            'address' => 'name@company.com',
                            'is_group' => false
                            ]
                    ],
                    'Name <name@company.com>',
                    ['COUNT',1,'This is a text body'],
                    ['MATCH',''],
                    [
                        [
                            'file.txt',
                            29,
                            'This is a file',
                            1,
                            'text/plain',
                            'attachment',
                            '839d0486dd1b91e520d456bb17c33148',
                            '',
                            ]
                        ],
                    0],
                [
                    'm0013',
                    '50032266 CAR 11_MNPA00A01_9PTX_H00 ATT N° 1467829. pdf',
                    [
                        [
                            'display' => 'NAME Firstname',
                            'address' => 'firstname.name@groupe-company.com',
                            'is_group' => false
                            ]
                    ],
                    'NAME Firstname <firstname.name@groupe-company.com>',
                    [
                        [
                            'display' => 'paul.dupont@company.com',
                            'address' => 'paul.dupont@company.com',
                            'is_group' => false
                            ]
                    ],
                    '"paul.dupont@company.com" <paul.dupont@company.com>',
                    ['COUNT',1,'Superviseur de voitures'],
                    ['MATCH',''],
                    [
                        [
                            '50032266 CAR 11_MNPA00A01_9PTX_H00 ATT N° 1467829.pdf',
                            10,
                            '',
                            0,
                            'application/pdf',
                            'attachment',
                            'ffe2cb0f5df4e2cfffd3931b6566f3cb',
                            '',
                            ]
                        ],
                    0],
                [
                    'm0014',
                    'Test message from Netscape Communicator 4.7',
                    [
                        [
                            'display' => 'Doug Sauder',
                            'address' => 'dwsauder@example.com',
                            'is_group' => false
                            ]
                    ],
                    'Doug Sauder <dwsauder@example.com>',
                    [
                        [
                            'display' => 'Joe Blow',
                            'address' => 'blow@example.com',
                            'is_group' => false
                            ]
                    ],
                    'Joe Blow <blow@example.com>',
                    ['COUNT',1,'Die Hasen und die'],
                    ['MATCH',''],
                    [],
                    0],
                [
                    'm0015',
                    'Up to $30 Off Multivitamins!',
                    [
                        [
                            'display' => 'Vitamart.ca',
                            'address' => 'service@vitamart.ca',
                            'is_group' => false
                            ]
                    ],
                    '"Vitamart.ca" <service@vitamart.ca>',
                    [
                        [
                            'display' => 'me@somewhere.com',
                            'address' => 'me@somewhere.com',
                            'is_group' => false
                            ]
                    ],
                    'me@somewhere.com',
                    ['COUNT',1,'Hi,'],
                    ['COUNT',1,'<strong>*How The Sale Works</strong>'],
                    [],
                    0],
                [
                    'm0016',
                    'Test message with multiple From headers',
                    [
                        [
                            'display' => 'Doug Sauder',
                            'address' => 'dwsauder@example.com',
                            'is_group' => false
                            ]
                    ],
                    'Doug Sauder <dwsauder@example.com>',
                    [
                        [
                            'display' => 'Joe Blow',
                            'address' => 'blow@example.com',
                            'is_group' => false
                            ]
                    ],
                    'Joe Blow <blow@example.com>',
                    ['COUNT',1,'Die Hasen und die'],
                    ['MATCH',''],
                    [],
                    0],
                [
                    'm0018',
                    '[Korea] Name',
                    [
                        [
                            'display' => 'name@company.com',
                            'address' => 'name@company.com',
                            'is_group' => false
                            ]
                    ],
                    '<name@company.com>',
                    [
                        [
                            'display' => 'name@company2.com',
                            'address' => 'name@company2.com',
                            'is_group' => false
                            ]
                    ],
                    '"name@company2.com" <name@company2.com>',
                    ['COUNT',1,'My traveling companions!'],
                    ['MATCH',''],
                    [
                        [
                            '사진.JPG',
                            174,
                            '',
                            0,
                            'image/jpeg',
                            'attachment',
                            '567f29989506f21cea8ac992d81ce4c1',
                            '',
                            ],
                        [
                            'ATT00001.txt',
                            25,
                            'iPhone',
                            1,
                            'text/plain',
                            'attachment',
                            '095f96b9d5a25d051ad425356745334f',
                            '',
                            ]
                        ],
                    0],
                [
                    'm0019',
                    'Re: Maya Ethnobotanicals - Emails',
                    [
                        [
                            'display' => 'sendeär',
                            'address' => 'sender@test.com',
                            'is_group' => false
                            ]
                    ],
                    'sendeär <sender@test.com>',
                    [
                        [
                            'display' => 'test',
                            'address' => 'test@asdasd.com',
                            'is_group' => false
                            ]
                    ],
                    '"test" <test@asdasd.com>',
                    ['COUNT',1,'captured'],
                    ['MATCH',''],
                    [],
                    0],
                [
                    'm0020',
                    '1',
                    [
                        [
                            'display' => 'Finntack Newsletter',
                            'address' => 'newsletter@finntack.com',
                            'is_group' => false
                            ]
                    ],
                    'Finntack Newsletter <newsletter@finntack.com>',
                    [
                        [
                            'display' => 'Clement Wong',
                            'address' => 'clement.wong@finntack.com',
                            'is_group' => false
                            ]
                    ],
                    'Clement Wong <clement.wong@finntack.com>',
                    ['MATCH',"1\r\n\r\n"],
                    ['COUNT',1,'<html>'],
                    [
                        [
                            'noname',
                            1432,
                            '',
                            0,
                            'text/calendar',
                            null,
                            'bf7bfb9b8dd11ff0c830b2388560d434',
                            '',
                            ]
                        ],
                    0],
                [
                    'm0021',
                    'occurs when divided into an array, and the last e of the array! Путін хуйло!!!!!!',
                    [
                        [
                            'display' => 'mail@exemple.com',
                            'address' => 'mail@exemple.com',
                            'is_group' => false
                            ]
                    ],
                    'mail@exemple.com',
                    [
                        [
                            'display' => 'mail@exemple.com',
                            'address' => 'mail@exemple.com',
                            'is_group' => false
                            ],
                        [
                            'display' => 'mail2@exemple3.com',
                            'address' => 'mail2@exemple3.com',
                            'is_group' => false
                            ],
                        [
                            'display' => 'mail3@exemple2.com',
                            'address' => 'mail3@exemple2.com',
                            'is_group' => false
                            ],
                    ],
                    'mail@exemple.com, mail2@exemple3.com, mail3@exemple2.com',
                    ['COUNT',1,'mini plain body'],
                    ['MATCH',''],
                    [],
                    0],
                [
                    'm0022',
                    '[PRJ-OTH] asdf  árvíztűrő tükörfúrógép',
                    [
                        [
                            'display' => 'sendeär',
                            'address' => 'sender@test.com',
                            'is_group' => false
                            ],
                    ],
                    'sendeär <sender@test.com>',
                    [
                        [
                            'display' => 'test',
                            'address' => 'test@asdasd.com',
                            'is_group' => false
                            ],
                    ],
                    '"test" <test@asdasd.com>',
                    ['COUNT',1,'captured'],
                    ['MATCH',''],
                    [],
                    0],
                [
                    'm0023',
                    'If you can read this you understand the example.',
                    [
                        [
                            'display' => 'Keith Moore',
                            'address' => 'moore@cs.utk.edu',
                            'is_group' => false
                            ],
                    ],
                    'Keith Moore <moore@cs.utk.edu>',
                    [
                        [
                            'display' => 'Keld Jørn Simonsen',
                            'address' => 'keld@dkuug.dk',
                            'is_group' => false
                            ],
                    ],
                    'Keld Jørn Simonsen <keld@dkuug.dk>',
                //CC = André Pirard <PIRARD@vm1.ulg.ac.be>
                    ['COUNT',1,'captured'],
                    ['MATCH',''],
                    [],
                    0],
                [
                    'm0024',
                    'Persil, abeilles ...',
                    [
                        [
                            'display' => 'John DOE',
                            'address' => 'blablafakeemail@provider.fr',
                            'is_group' => false
                            ],
                    ],
                    'John DOE <blablafakeemail@provider.fr>',
                    [
                        [
                            'display' => 'list-name',
                            'address' => 'list-name@list-domain.org',
                            'is_group' => false
                            ],
                    ],
                    'list-name <list-name@list-domain.org>',
                    ['MATCH',''],
                    ['MATCH',''],
                    [
                        [
                            'Biodiversité de semaine en semaine.doc',
                            27648,
                            '',
                            0,
                            'application/msword',
                            'attachment',
                            '57e8a3cf9cc29d5cde7599299a853560',
                            '',
                            ]
                        ],
                    0],
                [
                    'm0025',
                    'Testing MIME E-mail composing with cid',
                    [
                        [
                            'display' => 'Name',
                            'address' => 'name@company.com',
                            'is_group' => false
                            ],
                    ],
                    'Name <name@company.com>',
                    [
                        [
                            'display' => 'Name',
                            'address' => 'name@company2.com',
                            'is_group' => false
                            ],
                    ],
                    'Name <name@company2.com>',
                    ['COUNT',1,'Please use an HTML capable mail program to read'],
                    ['COUNT',1,'<center><h1>Testing MIME E-mail composing with cid</h1></center>'],
                    [
                        [
                            'logo.jpg',
                            2695,
                            '',
                            0,
                            'image/gif',
                            'inline',
                            '0f65fd0831e68da313a2dcc58286d009',
                            'IZqShSiOcB213NOfRLezbJyBjy08zKMaNHpGo9nxc49ywafxGZ',
                            ],
                        [
                            'background.jpg',
                            18255,
                            '',
                            0,
                            'image/gif',
                            'inline',
                            '840bdde001a8c8f6fb49ee641a89cdd8',
                            'QISn7+8fXB0RCQB2cyf8AcIQq2SMSQnzL',
                            ],
                        [
                            'attachment.txt',
                            2229,
                            'Sed pulvinar',
                            4,
                            'text/plain',
                            'attachment',
                            '71fff85a7960460bdd3c4b8f1ee9279b',
                            '',
                            ]
                        ],
                    2],
                [
                    'issue149',
                    "מענה 'אני לא נמצא': Invoice 02722027",
                    [
                        [
                            'display' => 'Name',
                            'address' => 'name@company.com',
                            'is_group' => false
                            ]
                    ],
                    'Name <name@company.com>',
                    [
                        [
                            'display' => 'name@company2.com',
                            'address' => 'name@company2.com',
                            'is_group' => false
                            ]
                    ],
                    'name@company2.com',
                    ['MATCH',"\n"],
                    ['COUNT',1,'<div dir="ltr"><br></div>'],
                    [
                        [
                            'attach01',
                            2,
                            'a',
                            1,
                            'application/octet-stream',
                            'attachment',
                            '04c1d5793efa97c956d011a8b3309f05',
                            '',
                            ]
                        ],
                    0],
                ];
        return $data;
    }

    /**
     * @dataProvider provideData
     */
    public function testFromPath(
        $mid,
        $subjectExpected,
        $fromAddressesExpected,
        $fromExpected,
        $toAddressesExpected,
        $toExpected,
        $textExpected,
        $htmlExpected,
        $attachmentsExpected,
        $countEmbeddedExpected
    ) {
        //Init
        $file = __DIR__.'/mails/'.$mid;
        $attach_dir = $this->tempdir('attach_'.$mid);

        //Load From Path
        $Parser = new Parser();
        $Parser->setPath($file);

        //Test Header : subject
        $this->assertEquals($subjectExpected, $Parser->getHeader('subject'));
        $this->assertArrayHasKey('subject', $Parser->getHeaders());

        //Test Header : from
        $this->assertEquals($fromAddressesExpected, $Parser->getAddresses('from'));
        $this->assertEquals($fromExpected, $Parser->getHeader('from'));
        $this->assertArrayHasKey('from', $Parser->getHeaders());

        //Test Header : to
        $this->assertEquals($toAddressesExpected, $Parser->getAddresses('to'));
        $this->assertEquals($toExpected, $Parser->getHeader('to'));
        $this->assertArrayHasKey('to', $Parser->getHeaders());

        //Test Invalid Header
        $this->assertFalse($Parser->getHeader('azerty'));
        $this->assertArrayNotHasKey('azerty', $Parser->getHeaders());

        //Test Raw Headers
        $this->assertIsString($Parser->getHeadersRaw());

        //Test  Body : text
        if ($textExpected[0] == 'COUNT') {
            $this->assertEquals($textExpected[1], substr_count($Parser->getMessageBody('text'), $textExpected[2]));
        } elseif ($textExpected[0] == 'MATCH') {
            $this->assertEquals($textExpected[1], $Parser->getMessageBody('text'));
        }

        //Test Body : html
        if ($htmlExpected[0] == 'COUNT') {
            $this->assertEquals($htmlExpected[1], substr_count($Parser->getMessageBody('html'), $htmlExpected[2]));
        } elseif ($htmlExpected[0] == 'MATCH') {
            $this->assertEquals($htmlExpected[1], $Parser->getMessageBody('html'));
        }

        //Test Nb Attachments
        $attachments = $Parser->getNestedAttachments2(['inline', 'attachment']);
        $this->assertEquals(count($attachmentsExpected), count($attachments));
        $iterAttachments = 0;

        //Test Attachments
        $attachmentsEmbeddedToCheck = [];
        if (count($attachmentsExpected) > 0) {
            //Save attachments
            $Parser->saveAttachments($attach_dir);

            foreach ($attachmentsExpected as $attachmentExpected) {
                //Test Exist Attachment
                $this->assertFileExists($attach_dir.$attachmentExpected[0]);

                //Test Filename Attachment
                $this->assertEquals($attachmentExpected[0], $attachments[$iterAttachments]->getFilename());

                //Test Size Attachment
                $this->assertEquals(
                    $attachmentExpected[1],
                    filesize($attach_dir.$attachments[$iterAttachments]->getFilename())
                );

                //Test Inside Attachment
                if ($attachmentExpected[2] != '' && $attachmentExpected[3] >0) {
                    $fileContent = file_get_contents(
                        $attach_dir.$attachments[$iterAttachments]->getFilename(),
                        FILE_USE_INCLUDE_PATH
                    );
                    $this->assertEquals($attachmentExpected[3], substr_count($fileContent, $attachmentExpected[2]));
                    $this->assertEquals(
                        $attachmentExpected[3],
                        substr_count($attachments[$iterAttachments]->getContent(), $attachmentExpected[2])
                    );
                }

                //Test ContentType Attachment
                $this->assertEquals($attachmentExpected[4], $attachments[$iterAttachments]->getContentType());

                //Test ContentDisposition Attachment
                $this->assertEquals($attachmentExpected[5], $attachments[$iterAttachments]->getContentDisposition());

                //Test md5 of Headers Attachment
                $this->assertEquals(
                    $attachmentExpected[6],
                    md5(serialize($attachments[$iterAttachments]->getHeaders()))
                );

                //Save embedded Attachments to check
                if ($attachmentExpected[7] != '') {
                    array_push($attachmentsEmbeddedToCheck, $attachmentExpected[7]);
                }

                $iterAttachments++;
            }
        } else {
            $this->assertEquals([], $Parser->saveAttachments($attach_dir));
        }

        //Test embedded Attachments
        $htmlEmbedded = $Parser->getMessageBody('htmlEmbedded');
        $this->assertEquals($countEmbeddedExpected, substr_count($htmlEmbedded, "data:"));

        if (!empty($attachmentsEmbeddedToCheck)) {
            foreach ($attachmentsEmbeddedToCheck as $itemExpected) {
                $this->assertEquals(1, substr_count($htmlEmbedded, $itemExpected));
            }
        }
    }

    /**
     * @dataProvider provideData
     */
    public function testFromText(
        $mid,
        $subjectExpected,
        $fromAddressesExpected,
        $fromExpected,
        $toAddressesExpected,
        $toExpected,
        $textExpected,
        $htmlExpected,
        $attachmentsExpected,
        $countEmbeddedExpected
    ) {
        //Init
        $file = __DIR__.'/mails/'.$mid;
        $attach_dir = $this->tempdir('attach_'.$mid);

        //Load From Text
        $Parser = new Parser();
        $Parser->setText(file_get_contents($file));

        //Test Header : subject
        $this->assertEquals($subjectExpected, $Parser->getHeader('subject'));
        $this->assertArrayHasKey('subject', $Parser->getHeaders());

        //Test Header : from
        $this->assertEquals($fromAddressesExpected, $Parser->getAddresses('from'));
        $this->assertEquals($fromExpected, $Parser->getHeader('from'));
        $this->assertArrayHasKey('from', $Parser->getHeaders());

        //Test Header : to
        $this->assertEquals($toAddressesExpected, $Parser->getAddresses('to'));
        $this->assertEquals($toExpected, $Parser->getHeader('to'));
        $this->assertArrayHasKey('to', $Parser->getHeaders());

        //Test Invalid Header
        $this->assertFalse($Parser->getHeader('azerty'));
        $this->assertArrayNotHasKey('azerty', $Parser->getHeaders());

        //Test Raw Headers
        $this->assertIsString($Parser->getHeadersRaw());

        //Test  Body : text
        if ($textExpected[0] == 'COUNT') {
            $this->assertEquals($textExpected[1], substr_count($Parser->getMessageBody('text'), $textExpected[2]));
        } elseif ($textExpected[0] == 'MATCH') {
            $this->assertEquals($textExpected[1], $Parser->getMessageBody('text'));
        }

        //Test Body : html
        if ($htmlExpected[0] == 'COUNT') {
            $this->assertEquals($htmlExpected[1], substr_count($Parser->getMessageBody('html'), $htmlExpected[2]));
        } elseif ($htmlExpected[0] == 'MATCH') {
            $this->assertEquals($htmlExpected[1], $Parser->getMessageBody('html'));
        }

        //Test Nb Attachments
        $attachments = $Parser->getNestedAttachments2(['inline', 'attachment']);
        $this->assertEquals(count($attachmentsExpected), count($attachments));
        $iterAttachments = 0;

        //Test Attachments
        $attachmentsEmbeddedToCheck = [];
        if (count($attachmentsExpected) > 0) {
            //Save attachments
            $Parser->saveAttachments($attach_dir);

            foreach ($attachmentsExpected as $attachmentExpected) {
                //Test Exist Attachment
                $this->assertFileExists($attach_dir.$attachmentExpected[0]);

                //Test Filename Attachment
                $this->assertEquals($attachmentExpected[0], $attachments[$iterAttachments]->getFilename());

                //Test Size Attachment
                $this->assertEquals(
                    $attachmentExpected[1],
                    filesize($attach_dir.$attachments[$iterAttachments]->getFilename())
                );

                //Test Inside Attachment
                if ($attachmentExpected[2] != '' && $attachmentExpected[3] >0) {
                    $fileContent = file_get_contents(
                        $attach_dir.$attachments[$iterAttachments]->getFilename(),
                        FILE_USE_INCLUDE_PATH
                    );
                    $this->assertEquals($attachmentExpected[3], substr_count($fileContent, $attachmentExpected[2]));
                    $this->assertEquals(
                        $attachmentExpected[3],
                        substr_count($attachments[$iterAttachments]->getContent(), $attachmentExpected[2])
                    );
                }

                //Test ContentType Attachment
                $this->assertEquals($attachmentExpected[4], $attachments[$iterAttachments]->getContentType());

                //Test ContentDisposition Attachment
                $this->assertEquals($attachmentExpected[5], $attachments[$iterAttachments]->getContentDisposition());

                //Test md5 of Headers Attachment
                $this->assertEquals(
                    $attachmentExpected[6],
                    md5(serialize($attachments[$iterAttachments]->getHeaders()))
                );

                //Save embedded Attachments to check
                if ($attachmentExpected[7] != '') {
                    array_push($attachmentsEmbeddedToCheck, $attachmentExpected[7]);
                }

                $iterAttachments++;
            }
        } else {
            $this->assertEquals([], $Parser->saveAttachments($attach_dir));
        }

        //Test embedded Attachments
        $htmlEmbedded = $Parser->getMessageBody('htmlEmbedded');
        $this->assertEquals($countEmbeddedExpected, substr_count($htmlEmbedded, "data:"));

        if (!empty($attachmentsEmbeddedToCheck)) {
            foreach ($attachmentsEmbeddedToCheck as $itemExpected) {
                $this->assertEquals(1, substr_count($htmlEmbedded, $itemExpected));
            }
        }
    }


    /**
     * @dataProvider provideData
     */
    public function testFromStream(
        $mid,
        $subjectExpected,
        $fromAddressesExpected,
        $fromExpected,
        $toAddressesExpected,
        $toExpected,
        $textExpected,
        $htmlExpected,
        $attachmentsExpected,
        $countEmbeddedExpected
    ) {
        //Init
        $file = __DIR__.'/mails/'.$mid;
        $attach_dir = $this->tempdir('attach_'.$mid);


        //Load From Path
        $Parser = new Parser();
        $Parser->setStream(fopen($file, 'r'));

        //Test Header : subject
        $this->assertEquals($subjectExpected, $Parser->getHeader('subject'));
        $this->assertArrayHasKey('subject', $Parser->getHeaders());

        //Test Header : from
        $this->assertEquals($fromAddressesExpected, $Parser->getAddresses('from'));
        $this->assertEquals($fromExpected, $Parser->getHeader('from'));
        $this->assertArrayHasKey('from', $Parser->getHeaders());

        //Test Header : to
        $this->assertEquals($toAddressesExpected, $Parser->getAddresses('to'));
        $this->assertEquals($toExpected, $Parser->getHeader('to'));
        $this->assertArrayHasKey('to', $Parser->getHeaders());

        //Test Invalid Header
        $this->assertFalse($Parser->getHeader('azerty'));
        $this->assertArrayNotHasKey('azerty', $Parser->getHeaders());

        //Test Raw Headers
        $this->assertIsString($Parser->getHeadersRaw());

        //Test  Body : text
        if ($textExpected[0] == 'COUNT') {
            $this->assertEquals($textExpected[1], substr_count($Parser->getMessageBody('text'), $textExpected[2]));
        } elseif ($textExpected[0] == 'MATCH') {
            $this->assertEquals($textExpected[1], $Parser->getMessageBody('text'));
        }

        //Test Body : html
        if ($htmlExpected[0] == 'COUNT') {
            $this->assertEquals($htmlExpected[1], substr_count($Parser->getMessageBody('html'), $htmlExpected[2]));
        } elseif ($htmlExpected[0] == 'MATCH') {
            $this->assertEquals($htmlExpected[1], $Parser->getMessageBody('html'));
        }

        //Test Nb Attachments
        $attachments = $Parser->getNestedAttachments2(['inline', 'attachment']);
        $this->assertEquals(count($attachmentsExpected), count($attachments));
        $iterAttachments = 0;

        //Test Attachments
        $attachmentsEmbeddedToCheck = [];
        if (count($attachmentsExpected) > 0) {
            //Save attachments
            $Parser->saveAttachments($attach_dir);

            foreach ($attachmentsExpected as $attachmentExpected) {
                //Test Exist Attachment
                $this->assertFileExists($attach_dir.$attachmentExpected[0]);

                //Test Filename Attachment
                $this->assertEquals($attachmentExpected[0], $attachments[$iterAttachments]->getFilename());

                //Test Size Attachment
                $this->assertEquals(
                    $attachmentExpected[1],
                    filesize($attach_dir.$attachments[$iterAttachments]->getFilename())
                );

                //Test Inside Attachment
                if ($attachmentExpected[2] != '' && $attachmentExpected[3] >0) {
                    $fileContent = file_get_contents(
                        $attach_dir.$attachments[$iterAttachments]->getFilename(),
                        FILE_USE_INCLUDE_PATH
                    );
                    $this->assertEquals($attachmentExpected[3], substr_count($fileContent, $attachmentExpected[2]));
                    $this->assertEquals(
                        $attachmentExpected[3],
                        substr_count($attachments[$iterAttachments]->getContent(), $attachmentExpected[2])
                    );
                }

                //Test ContentType Attachment
                $this->assertEquals($attachmentExpected[4], $attachments[$iterAttachments]->getContentType());

                //Test ContentDisposition Attachment
                $this->assertEquals($attachmentExpected[5], $attachments[$iterAttachments]->getContentDisposition());

                //Test md5 of Headers Attachment
                $this->assertEquals(
                    $attachmentExpected[6],
                    md5(serialize($attachments[$iterAttachments]->getHeaders()))
                );

                //Save embedded Attachments to check
                if ($attachmentExpected[7] != '') {
                    array_push($attachmentsEmbeddedToCheck, $attachmentExpected[7]);
                }

                $iterAttachments++;
            }
        } else {
            $this->assertEquals([], $Parser->saveAttachments($attach_dir));
        }

        //Test embedded Attachments
        $htmlEmbedded = $Parser->getMessageBody('htmlEmbedded');
        $this->assertEquals($countEmbeddedExpected, substr_count($htmlEmbedded, "data:"));

        if (!empty($attachmentsEmbeddedToCheck)) {
            foreach ($attachmentsEmbeddedToCheck as $itemExpected) {
                $this->assertEquals(1, substr_count($htmlEmbedded, $itemExpected));
            }
        }
    }

    public function testHeaderRetrievalIsCaseInsensitive()
    {
        //Init
        $file = __DIR__.'/mails/m0001';

        //Load From Path
        $Parser = new Parser();
        $Parser->setPath($file);

        $this->assertEquals($Parser->getRawHeader('From'), $Parser->getRawHeader('from'));
        $this->assertEquals($Parser->getHeader('From'), $Parser->getHeader('from'));
        $this->assertEquals($Parser->getAddresses('To'), $Parser->getAddresses('to'));
    }


    public function provideAttachmentsData()
    {
        return [
            [
                'm0001',
                [
                    'Content-Type: application/octet-stream; name=attach01
Content-Disposition: attachment; filename=attach01
Content-Transfer-Encoding: base64
X-Attachment-Id: f_hi0eudw60

YQo='
                ]
            ],
            [
                'm0002',
                [
                    'Content-Type: application/octet-stream; name=attach02
Content-Disposition: attachment; filename=attach02
Content-Transfer-Encoding: base64
X-Attachment-Id: f_hi0eyes30

TG9yZW0gaXBzdW0gZG9sb3Igc2l0IGFtZXQsIGNvbnNlY3RldHVyIGFkaXBpc2NpbmcgZWxpdC4g
VmVzdGlidWx1bSBjb25ndWUgc2VkIGFudGUgaWQgbGFvcmVldC4gUHJhZXNlbnQgZGljdHVtIHNh
cGllbiBpYWN1bGlzIG5pc2kgcGhhcmV0cmEsIHBvcnR0aXRvciBibGFuZGl0IG1hc3NhIGN1cnN1
cy4gRHVpcyByaG9uY3VzIG1hdXJpcyBhYyB1cm5hIHNlbXBlciwgc2VkIG1hbGVzdWFkYSBmZWxp
cyBpbnRlcmR1bS4gTG9yZW0gaXBzdW0gZG9sb3Igc2l0IGFtZXQsIGNvbnNlY3RldHVyIGFkaXBp
c2NpbmcgZWxpdC4gU2VkIHB1bHZpbmFyIGRpY3R1bSBvcm5hcmUuIEN1cmFiaXR1ciBldSBkb2xv
ciBmYWNpbGlzaXMsIHNhZ2l0dGlzIHB1cnVzIHByZXRpdW0sIGNvbnNlY3RldHVyIGVsaXQuIE51
bGxhIGVsZW1lbnR1bSBhdWN0b3IgdWx0cmljZXMuIE51bmMgZmVybWVudHVtIGRpY3R1bSBvZGlv
IHZlbCB0aW5jaWR1bnQuIFNlZCBjb25zZXF1YXQgdmVzdGlidWx1bSB2ZXN0aWJ1bHVtLiBQcm9p
biBwdWx2aW5hciBmZWxpcyB2aXRhZSBlbGVtZW50dW0gc3VzY2lwaXQuCgoKTG9yZW0gaXBzdW0g
ZG9sb3Igc2l0IGFtZXQsIGNvbnNlY3RldHVyIGFkaXBpc2NpbmcgZWxpdC4gVmVzdGlidWx1bSBj
b25ndWUgc2VkIGFudGUgaWQgbGFvcmVldC4gUHJhZXNlbnQgZGljdHVtIHNhcGllbiBpYWN1bGlz
IG5pc2kgcGhhcmV0cmEsIHBvcnR0aXRvciBibGFuZGl0IG1hc3NhIGN1cnN1cy4gRHVpcyByaG9u
Y3VzIG1hdXJpcyBhYyB1cm5hIHNlbXBlciwgc2VkIG1hbGVzdWFkYSBmZWxpcyBpbnRlcmR1bS4g
TG9yZW0gaXBzdW0gZG9sb3Igc2l0IGFtZXQsIGNvbnNlY3RldHVyIGFkaXBpc2NpbmcgZWxpdC4g
U2VkIHB1bHZpbmFyIGRpY3R1bSBvcm5hcmUuIEN1cmFiaXR1ciBldSBkb2xvciBmYWNpbGlzaXMs
IHNhZ2l0dGlzIHB1cnVzIHByZXRpdW0sIGNvbnNlY3RldHVyIGVsaXQuIE51bGxhIGVsZW1lbnR1
bSBhdWN0b3IgdWx0cmljZXMuIE51bmMgZmVybWVudHVtIGRpY3R1bSBvZGlvIHZlbCB0aW5jaWR1
bnQuIFNlZCBjb25zZXF1YXQgdmVzdGlidWx1bSB2ZXN0aWJ1bHVtLiBQcm9pbiBwdWx2aW5hciBm
ZWxpcyB2aXRhZSBlbGVtZW50dW0gc3VzY2lwaXQuCgoKTG9yZW0gaXBzdW0gZG9sb3Igc2l0IGFt
ZXQsIGNvbnNlY3RldHVyIGFkaXBpc2NpbmcgZWxpdC4gVmVzdGlidWx1bSBjb25ndWUgc2VkIGFu
dGUgaWQgbGFvcmVldC4gUHJhZXNlbnQgZGljdHVtIHNhcGllbiBpYWN1bGlzIG5pc2kgcGhhcmV0
cmEsIHBvcnR0aXRvciBibGFuZGl0IG1hc3NhIGN1cnN1cy4gRHVpcyByaG9uY3VzIG1hdXJpcyBh
YyB1cm5hIHNlbXBlciwgc2VkIG1hbGVzdWFkYSBmZWxpcyBpbnRlcmR1bS4gTG9yZW0gaXBzdW0g
ZG9sb3Igc2l0IGFtZXQsIGNvbnNlY3RldHVyIGFkaXBpc2NpbmcgZWxpdC4gU2VkIHB1bHZpbmFy
IGRpY3R1bSBvcm5hcmUuIEN1cmFiaXR1ciBldSBkb2xvciBmYWNpbGlzaXMsIHNhZ2l0dGlzIHB1
cnVzIHByZXRpdW0sIGNvbnNlY3RldHVyIGVsaXQuIE51bGxhIGVsZW1lbnR1bSBhdWN0b3IgdWx0
cmljZXMuIE51bmMgZmVybWVudHVtIGRpY3R1bSBvZGlvIHZlbCB0aW5jaWR1bnQuIFNlZCBjb25z
ZXF1YXQgdmVzdGlidWx1bSB2ZXN0aWJ1bHVtLiBQcm9pbiBwdWx2aW5hciBmZWxpcyB2aXRhZSBl
bGVtZW50dW0gc3VzY2lwaXQuCgpMb3JlbSBpcHN1bSBkb2xvciBzaXQgYW1ldCwgY29uc2VjdGV0
dXIgYWRpcGlzY2luZyBlbGl0LiBWZXN0aWJ1bHVtIGNvbmd1ZSBzZWQgYW50ZSBpZCBsYW9yZWV0
LiBQcmFlc2VudCBkaWN0dW0gc2FwaWVuIGlhY3VsaXMgbmlzaSBwaGFyZXRyYSwgcG9ydHRpdG9y
IGJsYW5kaXQgbWFzc2EgY3Vyc3VzLiBEdWlzIHJob25jdXMgbWF1cmlzIGFjIHVybmEgc2VtcGVy
LCBzZWQgbWFsZXN1YWRhIGZlbGlzIGludGVyZHVtLiBMb3JlbSBpcHN1bSBkb2xvciBzaXQgYW1l
dCwgY29uc2VjdGV0dXIgYWRpcGlzY2luZyBlbGl0LiBTZWQgcHVsdmluYXIgZGljdHVtIG9ybmFy
ZS4gQ3VyYWJpdHVyIGV1IGRvbG9yIGZhY2lsaXNpcywgc2FnaXR0aXMgcHVydXMgcHJldGl1bSwg
Y29uc2VjdGV0dXIgZWxpdC4gTnVsbGEgZWxlbWVudHVtIGF1Y3RvciB1bHRyaWNlcy4gTnVuYyBm
ZXJtZW50dW0gZGljdHVtIG9kaW8gdmVsIHRpbmNpZHVudC4gU2VkIGNvbnNlcXVhdCB2ZXN0aWJ1
bHVtIHZlc3RpYnVsdW0uIFByb2luIHB1bHZpbmFyIGZlbGlzIHZpdGFlIGVsZW1lbnR1bSBzdXNj
aXBpdC4K'
                ]
            ]
        ];
    }

    /**
     * @dataProvider provideAttachmentsData
     */
    public function testAttachmentGetMimePartStrFromPath($mid, $attachmentMimeParts)
    {
        // Init
        $file = __DIR__ . '/mails/' . $mid;

        $Parser = new Parser();
        $Parser->setPath($file);

        $i = 0;
        foreach ($Parser->getAttachments2() as $attachment) {
            $expectedMimePart = $attachmentMimeParts[$i];
            $this->assertEquals($expectedMimePart, $attachment->getMimePartStr());
            $i++;
        }
    }

    /**
     * @dataProvider provideAttachmentsData
     */
    public function testAttachmentGetMimePartStrFromStream($mid, $attachmentMimeParts)
    {
        // Init
        $file = __DIR__ . '/mails/' . $mid;

        $Parser = new Parser();
        $Parser->setStream(fopen($file, 'r'));

        $i = 0;
        foreach ($Parser->getAttachments2() as $attachment) {
            $expectedMimePart = $attachmentMimeParts[$i];
            $this->assertEquals($expectedMimePart, $attachment->getMimePartStr());
            $i++;
        }
    }

    /**
     * @dataProvider provideAttachmentsData
     */
    public function testAttachmentGetMimePartStrFromText($mid, $attachmentMimeParts)
    {
        // Init
        $file = __DIR__ . '/mails/' . $mid;

        $Parser = new Parser();
        $Parser->setText(file_get_contents($file));

        $i = 0;
        foreach ($Parser->getAttachments2() as $attachment) {
            $expectedMimePart = $attachmentMimeParts[$i];
            $this->assertEquals($expectedMimePart, $attachment->getMimePartStr());
            $i++;
        }
    }

    /**
     * @dataProvider providerRFC822AttachmentsWithDifferentTextTypes
     *
     * @param string $file Mail file path to parse
     * @param string $getType The type to give to getMessageBody
     * @param string $expected
     */
    public function testRFC822AttachmentPartsShouldNotBeIncludedInGetMessageBody($file, $getType, $expected)
    {
        $Parser = new Parser();
        $Parser->setPath($file);
        $this->assertEquals($expected, $Parser->getMessageBody($getType));
    }

    public function providerRFC822AttachmentsWithDifferentTextTypes()
    {
        return [
            'HTML-only message, with text-only RFC822 attachment, message should have empty text body' => [
                __DIR__.'/mails/issue158a',
                'text',
                ''
            ],
            'HTML-only message, with text-only RFC822 attachment, message should have HTML body' => [
                __DIR__.'/mails/issue158a',
                'html',
                "<html><body>An RFC 822 forward with a <em>HTML</em> body</body></html>\n"
            ],
            'Text-only message, with HTML-only RFC822 attachment, message should have empty HTML body' => [
                __DIR__.'/mails/issue158b',
                'html',
                ''
            ],
            'Text-only message, with HTML-only RFC822 attachment, message should have text body' => [
                __DIR__.'/mails/issue158b',
                'text',
                "A text/plain response to an REC822 message, with content filler to get it past the
200 character lower-limit in order to avoid preferring future HTML versions of the
body... filler filler filler filler filler filler filler filler filler.\n"
            ],
            'Text-only message, with text-only RFC822 attachment,
            should have text body but not include attachment part' => [
                __DIR__.'/mails/issue158c',
                'text',
                "An RFC822 forward of a PLAIN TEXT message with a plain-text body.\n"
            ],
            'Text-only message, with a text-only RFC822 attachment, message should have an empty HTML body' => [
                __DIR__.'/mails/issue158c',
                'html',
                ''
            ],
            'Multipart email with both text and html body, with RFC822 attachment also with a text and html body' => [
                __DIR__.'/mails/issue158d',
                'text',
                "This is the forward email send both emails will have both text and html variances available\n"
            ],
            'Multipart with both text and html body, RFC822 attachment also with text and html' => [
                __DIR__.'/mails/issue158d',
                'html',
                '<html><body><div>This is the forward email send both
emails will have both text and html
variances available &nbsp;</div></body></html>'
            ],
        ];
    }

    /**
     * Ensure the middleware can modify mime part data
     */
    public function testMiddleware()
    {
        $middlewareCalled = false;

        //Init
        $file = __DIR__.'/mails/m0001';

        //Load From Path
        $Parser = new Parser();

        // add literal function middleware
        $Parser->addMiddleware(function ($mimePart, $next) use (&$middlewareCalled) {
            $middlewareCalled = true;
            $part = $mimePart->getPart();

            if (isset($part['headers']['from'])) {
                $part['headers']['from'] = 'Middleware';
            }
            $mimePart->setPart($part);

            return $next($mimePart);
        });

        // executes the middleware
        $Parser->setPath($file);

        $this->assertTrue($middlewareCalled, 'Middleware was was not called.');
        $this->assertEquals($Parser->getHeader('from'), 'Middleware', 'From header modification failed');

        // run middlware in factory
        $middlewareCallCount = 0;
        $mimePart = new MimePart('1', []);
        $middleWare = new MiddleWare(function ($mimePart, $next) use (&$middlewareCallCount) {
            $middlewareCallCount++;
            return $next($mimePart);
        });
        $middlewareStack = MiddlewareStack::factory([$middleWare, $middleWare]);

        // executes the middleware
        $middlewareStack->parse($mimePart);

        $this->assertTrue($middlewareCallCount == 2, 'Middleware was was not called.');
    }

    /**
     * Ensure MimePart has ArrayAccess
     */
    public function testMimePart()
    {
        $id = '1';
        $part = ['foo' => 'bar'];
        $part2 = ['fooz' => 'barz'];
        $mimePart = new MimePart($id, $part);

        // created
        $this->assertEquals($mimePart->getId(), $id);
        $this->assertEquals($mimePart->getPart(), $part);
        $this->assertTrue(isset($mimePart['foo']));
        $this->assertEquals($mimePart['foo'], $part['foo']);

        // new part
        $mimePart->setPart($part2);
        $this->assertEquals($mimePart->getId(), $id);
        $this->assertEquals($mimePart->getPart(), $part2);
        $this->assertTrue(isset($mimePart['fooz']));
        $this->assertEquals($mimePart['fooz'], $part2['fooz']);

        // modification and unset
        $mimePart['fooz'] = 'foozbarz';
        $mimePart[] = 'indexedFoo';
        $this->assertEquals($mimePart['fooz'], 'foozbarz');
        $this->assertEquals($mimePart[0], 'indexedFoo');
        unset($mimePart['fooz']);
        unset($mimePart[0]);
        $this->assertTrue(isset($mimePart['fooz']) === false);
        $this->assertTrue(isset($mimePart[0]) === false);
    }

    public function testParsingFileWithoutEndOfLineFromText()
    {
        $file = 'From: mail@exemple.com
To: mail@exemple.com, mail2@exemple3.com, mail3@exemple2.com
Subject: =?windows-1251?Q?occurs_when_divided_into_an_array?=
 =?windows-1251?Q?=2C_and_the_last_e_of_the_array!_=CF=F3=F2?=
 =?windows-1251?Q?=B3=ED_=F5=F3=E9=EB=EE!!!!!!?=
MIME-Version: 1.0
Content-Type: text/plain; charset=us-ascii
Content-Transfer-Encoding: 7bit

mini plain body';

        $ParserByText = new Parser();
        $ParserByText->setText($file);
        $this->assertStringContainsString('mini plain body', $ParserByText->getMessageBody('text'));

        $ParserByPath = new Parser();
        $temp = tmpfile();
        fwrite($temp, $file);
        rewind($temp);
        $metaDatas = stream_get_meta_data($temp);
        $tmpFilename = $metaDatas['uri'];
        $ParserByPath->setPath($tmpFilename);
        $this->assertStringContainsString('mini plain body', $ParserByPath->getMessageBody('text'));
    }

    public function testParsingFileWithoutEndOfLineFromPath()
    {
        $file = 'From: mail@exemple.com
To: mail@exemple.com, mail2@exemple3.com, mail3@exemple2.com
Subject: =?windows-1251?Q?occurs_when_divided_into_an_array?=
 =?windows-1251?Q?=2C_and_the_last_e_of_the_array!_=CF=F3=F2?=
 =?windows-1251?Q?=B3=ED_=F5=F3=E9=EB=EE!!!!!!?=
MIME-Version: 1.0
Content-Type: text/plain; charset=us-ascii
Content-Transfer-Encoding: 7bit

mini plain body';

        $ParserByPath = new Parser();
        $temp = tmpfile();
        fwrite($temp, $file);
        rewind($temp);
        $metaDatas = stream_get_meta_data($temp);
        $tmpFilename = $metaDatas['uri'];
        $ParserByPath->setPath($tmpFilename);
        $this->assertStringContainsString('mini plain body', $ParserByPath->getMessageBody('text'));
    }

    public function testParsingFileWithoutEndOfLineFromStream()
    {
        $file = 'From: mail@exemple.com
To: mail@exemple.com, mail2@exemple3.com, mail3@exemple2.com
Subject: =?windows-1251?Q?occurs_when_divided_into_an_array?=
 =?windows-1251?Q?=2C_and_the_last_e_of_the_array!_=CF=F3=F2?=
 =?windows-1251?Q?=B3=ED_=F5=F3=E9=EB=EE!!!!!!?=
MIME-Version: 1.0
Content-Type: text/plain; charset=us-ascii
Content-Transfer-Encoding: 7bit

mini plain body';

        $ParserByStream = new Parser();
        $temp = tmpfile();
        fwrite($temp, $file);
        rewind($temp);
        $ParserByStream->setStream($temp);
        $this->assertStringContainsString('mini plain body', $ParserByStream->getMessageBody('text'));
    }

    public function testCharsetSupportedAsAnAlias()
    {
        // Init
        $file = __DIR__ . '/mails/failure.eml';

        $Parser = new Parser();
        $Parser->setPath($file);
        $this->assertEquals('<foo@bar.de>', $Parser->getHeader('from'));
        $this->assertStringContainsString('次の受信者またはグル?プへの配信に失?·筏蓼筏?', $Parser->getMessageBody('text'));
    }

    public function testCharsetNotSupportedByMBString()
    {
        // Init
        $file = __DIR__ . '/mails/issue212';

        $Parser = new Parser();
        $Parser->setPath($file);
        $this->assertEquals(
            'Automatyczna odpowiedź: Piotrze, test z 6 miesięcy nauki ciągle na Ciebie czeka',
            $Parser->getHeader('subject')
        );
    }

    public function testRecursiveMessageThatReturnContentTypeError()
    {
        $Parser = new Parser();
        $Parser->setPath(__DIR__.'/mails/issue274.eml');

        $this->assertEquals('guest@localhost', $Parser->getRawHeader('from')[0]);
        $this->assertStringContainsString('ligne 1', $Parser->getMessageBody('text'));
        $this->assertStringContainsString('ligne 1', $Parser->getMessageBody('html'));

        $attachments = $Parser->getAttachments2();
        $this->assertCount(5, $attachments);

        foreach ($attachments as $key => $attachment) {
            $attachmentsName = [
                0 => 'Hello from SwiftMailer.docx',
                1 => 'Hello from SwiftMailer.pdf',
                2 => 'Hello from SwiftMailer.odt',
                3 => 'Cours-Tutoriels-Serge-Tahé-1568x268.png',
                4 => 'test-localhost.eml',
            ];

            $this->assertEquals($attachmentsName[$key], $attachment->getFilename());
        }
    }

    public function testGetterMethods()
    {
        $file = __DIR__.'/mails/m0001';

        $Parser = new Parser();
        $Parser->setStream(fopen($file, 'r'));

        $this->assertIsResource($Parser->getResource());
        $this->assertIsResource($Parser->getStream());
        $this->assertIsArray($Parser->getParts());
        $this->assertInstanceOf(CharsetManager::class, $Parser->getCharset());

        $this->assertNull($Parser->getData());
    }

    public function testTextGetter()
    {
        $file = __DIR__.'/mails/m0001';

        $Parser = new Parser();
        $Parser->setText(file_get_contents($file));

        $this->assertStringEqualsFile($file, $Parser->getData());
    }

    public function testGetTextRaw()
    {
        $file = __DIR__.'/mails/issue230';

        $Parser = new Parser();
        $Parser->setText(file_get_contents($file));

        $this->assertStringContainsString('=C2=A9 2019 Science', $Parser->getTextRaw());
        $this->assertStringContainsString('© 2019 Science', $Parser->getText());

        $this->assertStringContainsString('=C2=A9 2019 Science', $Parser->getHtmlRaw());
        $this->assertStringContainsString('© 2019 Science', $Parser->getHtml());
    }

    public function testSetCharsetManager()
    {
        $file = __DIR__.'/mails/issue230';

        $newCharset = $this->createMock(\PhpMimeMailParser\Contracts\CharsetManager::class);

        $Parser = new Parser();
        $Parser->setCharsetManager($newCharset);
        $Parser->setText(file_get_contents($file));
        $this->assertEmpty($Parser->getText());
    }

    public function testSetContentTransferEncodingManager()
    {
        $file = __DIR__.'/mails/issue230';

        $newContentTransferEncoding = $this->createMock(\PhpMimeMailParser\Contracts\ContentTransferEncodingManager::class);

        $Parser = new Parser();
        $Parser->setContentTransferEncodingManager($newContentTransferEncoding);
        $Parser->setText(file_get_contents($file));
        $this->assertEmpty($Parser->getText());
    }

    public function testSetMimeHeaderEncodingManager()
    {
        $file = __DIR__.'/mails/issue212';

        $newMimeHeaderEncoding = $this->createMock(\PhpMimeMailParser\Contracts\MimeHeaderEncodingManager::class);

        $Parser = new Parser();
        $Parser->setMimeHeaderEncodingManager($newMimeHeaderEncoding);
        $Parser->setText(file_get_contents($file));

        $this->assertEmpty($Parser->getHeader('subject'));
    }

    public function testNoPartFiltered()
    {
        $file = __DIR__.'/mails/m0124';

        $Parser = new Parser();
        $Parser->setText(file_get_contents($file));

        $this->assertEmpty($Parser->getTextRaw());
        $this->assertEmpty($Parser->getHtmlRaw());
        $this->assertEmpty($Parser->getText());
        $this->assertEmpty($Parser->getHtml());
    }



    public function testIssue269()
    {
        $file = __DIR__.'/mails/issue158a';

        $Parser = new Parser();
        $Parser->setText(file_get_contents($file));

        $this->assertEmpty($Parser->getText());
        $this->assertStringContainsString('An RFC 822 forward', $Parser->getHtml());

        $attachments = $Parser->getAttachments2();
        $this->assertCount(1, $attachments);
        $this->assertEquals('message/rfc822', $attachments[0]->getContentType());
        $this->assertEquals('Test 5.eml', $attachments[0]->getFilename());
    }
}
