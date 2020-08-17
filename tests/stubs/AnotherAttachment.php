<?php
namespace Tests\PhpMimeMailParser\Stubs;

use \PhpMimeMailParser\Contracts\AttachmentInterface;
use \PhpMimeMailParser\Contracts\ParserInterface;
use \PhpMimeMailParser\Entity;

class AnotherAttachment implements AttachmentInterface
{
    public function getFilename(): string
    {
        return '';
    }

    public function getContentType(): string
    {
        return '';
    }

    public function getContentDisposition(): string
    {
        return '';
    }

    public function getContentID(): string
    {
        return '';
    }

    public function getHeaders(): array
    {
        return [];
    }

    public function getStream(): void
    {
    }

    public function getContent(): string
    {
        return '';
    }

    public function getMimePartStr(): string
    {
        return '';
    }

    public function save(
        $attachDir,
        $filenameStrategy = ParserInterface::ATTACHMENT_DUPLICATE_SUFFIX
    ): string {
        return '';
    }

    public static function create(
        Entity $part
    ): \Tests\PhpMimeMailParser\Stubs\AnotherAttachment {
        return new self();
    }
}
