<?php

declare(strict_types=1);

namespace PhpMimeMailParser;

use PhpMimeMailParser\Contracts\AttachmentInterface;
use PhpMimeMailParser\Contracts\CharsetManager;
use PhpMimeMailParser\Contracts\ContentTransferEncodingManager;
use PhpMimeMailParser\Contracts\MimeHeaderEncodingManager;

final class ParserConfig
{
    /**
     * @var \PhpMimeMailParser\Contracts\CharsetManager
     */
    private $charsetManager;
    /**
     * @var \PhpMimeMailParser\Contracts\ContentTransferEncodingManager
     */
    private $contentTransferEncodingManager;
    /**
     * @var \PhpMimeMailParser\Contracts\MimeHeaderEncodingManager
     */
    private $mimeHeaderEncodingManager;
    /**
     * @var \PhpMimeMailParser\Contracts\AttachmentInterface
     */
    private $attachmentInterface;

    public function __construct()
    {
        $this->setCharsetManager(new Charset());
        $this->setContentTransferEncodingManager(new ContentTransferDecoder());
        $this->setMimeHeaderEncodingManager(new MimeHeaderDecoder(
            $this->getCharsetManager(),
            $this->getContentTransferEncodingManager()
        ));
        $this->setAttachmentInterface(new Attachment);
    }

    public function setCharsetManager(CharsetManager $charsetManager): void
    {
        $this->charsetManager = $charsetManager;
    }

    public function setContentTransferEncodingManager(ContentTransferEncodingManager $contentTransferEncodingManager): void
    {
        $this->contentTransferEncodingManager = $contentTransferEncodingManager;
    }

    public function setMimeHeaderEncodingManager(MimeHeaderEncodingManager $mimeHeaderEncodingManager): void
    {
        $this->mimeHeaderEncodingManager = $mimeHeaderEncodingManager;
    }

    public function setAttachmentInterface(AttachmentInterface $attachmentInterface): void
    {
        $this->attachmentInterface = $attachmentInterface;
    }

    public function getCharsetManager(): CharsetManager
    {
        return $this->charsetManager;
    }

    public function getContentTransferEncodingManager(): ContentTransferEncodingManager
    {
        return $this->contentTransferEncodingManager;
    }

    public function getMimeHeaderEncodingManager(): MimeHeaderEncodingManager
    {
        return $this->mimeHeaderEncodingManager;
    }

    public function getAttachmentInterface(): AttachmentInterface
    {
        return $this->attachmentInterface;
    }
}
