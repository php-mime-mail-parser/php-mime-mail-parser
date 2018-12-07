<?php
namespace PhpMimeMailParser;

use PhpMimeMailParser\Parser;
use PhpMimeMailParser\Attachment;
use PhpMimeMailParser\Exception;

/**
 * Test Attachment of php-mime-mail-parser
 *
 * Fully Tested Mailparse Extension Wrapper for PHP 5.4+
 *
 */
class AttachmentTest extends \PHPUnit\Framework\TestCase
{

    public function testSaveFileFromAttachment()
    {

        $save_dir = 'tests/images/tmp/';

        //CLEAN OUT OLD DIRECTORY
        $old_files = glob($save_dir.'*');
        foreach ($old_files as $old_file) {
            unlink($old_file);
        }

        $emails =  glob('tests/mails/iateadonut001*');
        foreach ($emails as $email) {

            $fp = fopen($email, 'r');
            $contents = '';
            while (!feof($fp)) {
                $contents .= fread($fp, 1024);
            }

            $message = new Parser();
            $message->setText($contents);

            $attachments = $message->getAttachments();
            foreach ($attachments as $a) {
                $file_path = $a->save($save_dir);
                $a_md5 = $a->getMd5();
                $f_md5 = md5_file($file_path);
                $this->assertEquals($a_md5, $f_md5, $a_md5 . ' - from $a; ' . $f_md5 . ' - from file - ' . $file_path);
            }
        }  

    }

    public function testSaveFileFromParser()
    {
        $save_dir = 'tests/images/tmp/';

        //CLEAN OUT OLD DIRECTORY
        $old_files = glob($save_dir.'*');
        foreach ($old_files as $old_file) {
            unlink($old_file);
        }

        $emails =  glob('tests/mails/iateadonut001*');
        foreach ($emails as $email) {
            $fp = fopen($email, 'r');
            $contents = '';
            while (!feof($fp)) {
                $contents .= fread($fp, 1024);
            }

            $message = new Parser();
            $message->setText($contents);

            $message->saveAttachments($save_dir);

            $attachments = $message->getAttachments();
            foreach ($attachments as $a) {
                $file_path = $save_dir . $a->getFilename();
                $c_md5 = md5($a->getContent());
                $a_md5 = $a->getMd5();
                $f_md5 = md5_file($file_path);
                $this->assertEquals($a_md5, $f_md5, $a_md5 . ' - from $a; ' . $f_md5 . ' - from file - ' . $file_path);
                $this->assertEquals($c_md5, $f_md5, $c_md5 . ' - from $a->getContent(); ' . $f_md5 . ' - from file - ' . $file_path);
            } 
        }
    }
}
