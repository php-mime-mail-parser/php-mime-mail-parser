<?php

namespace PhpMimeMailParser;

/**
 * Attachment of php-mime-mail-parser
 *
 * Fully Tested Mailparse Extension Wrapper for PHP 5.4+
 *
 */

class Attachment
{

    /**
    * @var $filename Filename
    */
    public $filename;
    /**
     * @var $uniqueFilename Unique filename using uniqid()
     */
    public $uniqueFilename;
    /**
    * @var $contentType Mime Type
    */
    public $contentType;
    /**
    * @var $content File Content
    */
    private $content;
    /**
    * @var $extension Filename extension
    */
    private $extension;
    /**
    * @var $contentDisposition Content-Disposition (attachment or inline)
    */
    public $contentDisposition;
    /**
    * @var $contentId Content-ID
    */
    public $contentId;
    /**
    * @var $headers An Array of the attachment headers
    */
    public $headers;

    private $stream;

    public function __construct(
        $filename,
        $uniqueFilename,
        $contentType,
        $stream,
        $contentDisposition = 'attachment',
        $contentId = '',
        $headers = array()
    ) {
        $this->filename = $filename;

        // Explode filename to get extension
        $this->uniqueFilename = $uniqueFilename . "." . array_pop(explode(".", $filename));

        $this->contentType = $contentType;
        $this->stream = $stream;
        $this->content = null;
        $this->contentDisposition = $contentDisposition;
        $this->contentId = $contentId;
        $this->headers = $headers;
    }

    /**
    * retrieve the attachment filename
    * @return String
    */
    public function getFilename()
    {
        return $this->filename;
    }

    /**
     * retrieve the attachment unique filename
     * @return String
     */
    public function getUniqueFilename()
    {
        return $this->uniqueFilename;
    }


    /**
    * Retrieve the Attachment Content-Type
    * @return String
    */
    public function getContentType()
    {
        return $this->contentType;
    }

    /**
    * Retrieve the Attachment Content-Disposition
    * @return String
    */
    public function getContentDisposition()
    {
        return $this->contentDisposition;
    }

    /**
    * Retrieve the Attachment Content-ID
    * @return String
    */
    public function getContentID()
    {
        return $this->contentId;
    }

    /**
    * Retrieve the Attachment Headers
    * @return String
    */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
    * Read the contents a few bytes at a time until completed
    * Once read to completion, it always returns false
    * @return String
    * @param $bytes Int[optional]
    */
    public function read($bytes = 2082)
    {
        return feof($this->stream) ? false : fread($this->stream, $bytes);
    }

    /**
    * Retrieve the file content in one go
    * Once you retreive the content you cannot use MimeMailParser_attachment::read()
    * @return String
    */
    public function getContent()
    {
        if ($this->content === null) {
            fseek($this->stream, 0);
            while (($buf = $this->read()) !== false) {
                $this->content .= $buf;
            }
        }
        return $this->content;
    }
}
