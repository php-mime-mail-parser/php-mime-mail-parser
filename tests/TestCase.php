<?php
namespace Tests\PhpMimeMailParser;

use Symfony\Component\Filesystem\Filesystem;

/**
 * Test case of php-mime-mail-parser
 */
abstract class TestCase extends \PHPUnit\Framework\TestCase
{
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
}
