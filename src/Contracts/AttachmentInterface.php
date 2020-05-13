<?php

namespace PhpMimeMailParser\Contracts;

interface AttachmentInterface
{

    /**
     * retrieve the attachment filename
     *
     * @return string
     */
    public function getFilename(): string;

    /**
     * Retrieve the Attachment Content-Type
     *
     * @return string
     */
    public function getContentType(): string;

    /**
     * Retrieve the Attachment Content-Disposition
     *
     * @return string
     */
    public function getContentDisposition(): string;

    /**
     * Retrieve the Attachment Content-ID
     *
     * @return string
     */
    public function getContentID(): ?string;

    /**
     * Retrieve the Attachment Headers
     *
     * @return array
     */
    public function getHeaders(): array;

    /**
     * Get a handle to the stream
     *
     * @return resource
     */
    public function getStream();

    /**
     * Retrieve the file content in one go
     *
     * @return string
     */
    public function getContent(): string;

    /**
     * Get mime part string for this attachment
     *
     * @return string
     */
    public function getMimePartStr(): string;

    /**
     * Save the attachment individually
     *
     * @param string $attach_dir
     * @param string $filenameStrategy
     *
     * @return string
     */
    public function save(
        $attach_dir,
        $filenameStrategy = ParserInterface::ATTACHMENT_DUPLICATE_SUFFIX
    ): string;

    /**
     * Get mime part string for this attachment
     *
     * @param string   $filename
     * @param string   $contentType
     * @param resource $stream
     * @param string   $contentDisposition
     * @param string   $contentId
     * @param array    $headers
     * @param string   $mimePartStr
     */
    public static function create(
        $filename,
        $contentType,
        $stream,
        $contentDisposition = 'attachment',
        $contentId = '',
        $headers = [],
        $mimePartStr = ''
    );
}
