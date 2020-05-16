<?php
namespace Tests\PhpMimeMailParser\Stubs;

use \PhpMimeMailParser\Contracts\AttachmentInterface;
use \PhpMimeMailParser\Contracts\ParserInterface;

class AnotherAttachment implements AttachmentInterface
{
    public function getFilename(): string
    {
    }

    public function getContentType(): string
    {
    }

    public function getContentDisposition(): string
    {
    }

    public function getContentID(): ?string
    {
    }

    public function getHeaders(): array
    {
    }

    public function getStream()
    {
    }

    public function getContent(): string
    {
    }

    public function getMimePartStr(): string
    {
    }

    public function save(
        $attach_dir,
        $filenameStrategy = ParserInterface::ATTACHMENT_DUPLICATE_SUFFIX
    ): string {
    }

    public static function create(
        $filename,
        $contentType,
        $stream,
        $contentDisposition = 'attachment',
        $contentId = '',
        $headers = [],
        $mimePartStr = ''
    ) {
        return new self();
    }
}
