<?php
namespace Tests\PhpMimeMailParser;

use Symfony\Component\Filesystem\Filesystem;

/**
 * Test case of php-mime-mail-parser
 */
abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    /**
     * @var mixed[]
     */
    private $pathsToRemove = [];

    protected function setUp(): void
    {
        // Common setup procedures
    }

    protected function tempdir(string $prefix = ''): string
    {
        do {
            $tempnam = tempnam(sys_get_temp_dir(), 'php-mime-mail-parser_' . $prefix);
            unlink($tempnam);

            if (@mkdir($tempnam, 0700)) {
                break;
            }
        } while (true);

        $this->pathsToRemove[] = $tempnam;

        // Other code expects this to end with a slash.
        return $tempnam . DIRECTORY_SEPARATOR;
    }

    protected function tearDown(): void
    {
        $filesystem = new Filesystem();
        $filesystem->remove($this->pathsToRemove);

        $this->pathsToRemove = [];
    }
    public function provideEmails($here): array
    {
        $emailsFile = glob(__DIR__.'/emails/*.php');

        $emails= [];
        foreach ($emailsFile as $emailFile) {
            $id = basename($emailFile, '.php');
            $emails[$id] = array_merge_recursive(
                ["id" => $id],
                include $emailFile
            )
            ;
        }
        return $emails;
    }
    public function provideBodyEmails(): array
    {
        $emailsFile = glob(__DIR__.'/emails/*.php');

        $emails= [];
        foreach ($emailsFile as $emailFile) {
            $id = basename($emailFile, '.php');
            $emails[$id] = array_merge_recursive(
                ["id" => $id],
                include $emailFile
            )
            ;
        }

        $bodyEmails=[];

        foreach ($emails as $email) {
            $bodyEmails[$email['id']] = [
                "id" => $email['id'],
                "textBody" => $email['textBody'],
                "htmlBody" => $email['htmlBody'],
            ];
        }

        return $bodyEmails;
    }
    public function provideAttachmentEmails(): array
    {
        $emailsFile = glob(__DIR__.'/emails/*.php');

        $emails= [];
        foreach ($emailsFile as $emailFile) {
            $id = basename($emailFile, '.php');
            $emails[$id] = array_merge_recursive(
                ["id" => $id],
                include $emailFile
            )
            ;
        }

        $attachmentEmails=[];

        foreach ($emails as $email) {
            $attachmentEmails[$email['id']] = [
                "id" => $email['id'],
                "attachments" => $email['attachments'],
            ];
        }

        return $attachmentEmails;
    }
}
