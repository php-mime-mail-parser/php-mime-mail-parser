<?php

namespace PhpMimeMailParser;

use function var_dump;

/**
 * Attachment of php-mime-mail-parser
 *
 * Fully Tested Mailparse Extension Wrapper for PHP 5.4+
 *
 */
class Attachment
{
    /**
     * Filename
     */
    protected string $filename;

    /**
     * Mime Type
     */
    protected string $contentType;

    /**
     * File Content
     */
    protected ?string $content = null;

    /**
     * Content-Disposition (attachment or inline)
     */
    protected string $contentDisposition;

    /**
     * Content-ID
     */
    protected string $contentId;

    /**
     * An Array of the attachment headers
     */
    protected array $headers;

    /**
     * Stream resource
     */
    protected mixed $stream;

    /**
     * Mime part string
     */
    protected string $mimePartStr;

    /**
     * Max duplicate number
     */
    public int $maxDuplicateNumber = 100;

    /**
     * Attachment constructor.
     */
    public function __construct(
        string $filename,
        string $contentType,
        mixed $stream,
        string $contentDisposition = 'attachment',
        string $contentId = '',
        array $headers = [],
        string $mimePartStr = ''
    ) {
        $this->filename = $filename;
        $this->contentType = $contentType;
        $this->stream = $stream;
        $this->content = null;
        $this->contentDisposition = $contentDisposition;
        $this->contentId = $contentId;
        $this->headers = $headers;
        $this->mimePartStr = $mimePartStr;
    }

    /**
     * retrieve the attachment filename
     */
    public function getFilename(): string
    {
        return $this->filename;
    }

    /**
     * Retrieve the Attachment Content-Type
     */
    public function getContentType(): string
    {
        return $this->contentType;
    }

    /**
     * Retrieve the Attachment Content-Disposition
     */
    public function getContentDisposition(): string
    {
        return $this->contentDisposition;
    }

    /**
     * Retrieve the Attachment Content-ID
     */
    public function getContentID(): string
    {
        return $this->contentId;
    }

    /**
     * Retrieve the Attachment Headers
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Get a handle to the stream
     */
    public function getStream(): mixed
    {
        return $this->stream;
    }

    /**
     * Rename a file if it already exists at its destination.
     * Renaming is done by adding a duplicate number to the file name. E.g. existingFileName_1.ext.
     * After a max duplicate number, renaming the file will switch over to generating a random suffix.
     *
     * @param string $fileName  Complete path to the file.
     * @return string           The suffixed file name.
     */
    protected function suffixFileName(string $fileName): string
    {
        $pathInfo = pathinfo($fileName);
        $dirname = $pathInfo['dirname'].DIRECTORY_SEPARATOR;
        $filename = $pathInfo['filename'];
        $extension  = empty($pathInfo['extension']) ? '' : '.'.$pathInfo['extension'];

        $i = 0;
        do {
            $i++;

            if ($i > $this->maxDuplicateNumber) {
                $duplicateExtension = uniqid();
            } else {
                $duplicateExtension = $i;
            }

            $resultName = $dirname.$filename."_$duplicateExtension".$extension;
        } while (file_exists($resultName));

        return $resultName;
    }

    /**
     * Read the contents a few bytes at a time until completed
     * Once read to completion, it always returns false
     *
     * @param int $bytes (default: 2082)
     */
    public function read(int $bytes = 2082): string|false
    {
        return feof($this->stream) ? false : fread($this->stream, $bytes);
    }

    /**
     * Retrieve the file content in one go
     * Once you retrieve the content you cannot use MimeMailParser_attachment::read()
     */
    public function getContent(): ?string
    {
        if ($this->content === null) {
            fseek($this->stream, 0);
            while (($buf = $this->read()) !== false) {
                $this->content .= $buf;
            }
        }

        return $this->content;
    }

    /**
     * Get mime part string for this attachment
     */
    public function getMimePartStr(): string
    {
        return $this->mimePartStr;
    }

    /**
     * Save the attachment individually
     *
     * @param string $attach_dir
     * @param string $filenameStrategy
     *
     * @throws Exception
     */
    public function save(
        string $attach_dir,
        string $filenameStrategy = Parser::ATTACHMENT_DUPLICATE_SUFFIX
    ): string|false {
        $attach_dir = rtrim($attach_dir, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
        if (!is_dir($attach_dir)) {
            mkdir($attach_dir);
        }

        // Determine filename
        switch ($filenameStrategy) {
            case Parser::ATTACHMENT_RANDOM_FILENAME:
                $fileInfo = pathinfo($this->getFilename());
                $extension  = empty($fileInfo['extension']) ? '' : '.'.$fileInfo['extension'];
                $attachment_path = $attach_dir.bin2hex(random_bytes(16)).$extension;
                break;
            case Parser::ATTACHMENT_DUPLICATE_THROW:
            case Parser::ATTACHMENT_DUPLICATE_SUFFIX:
                $attachment_path = $attach_dir.$this->getFilename();
                break;
            default:
                throw new Exception('Invalid filename strategy argument provided.');
        }

        // Handle duplicate filename
        if (file_exists($attachment_path)) {
            switch ($filenameStrategy) {
                case Parser::ATTACHMENT_DUPLICATE_THROW:
                    throw new Exception('Could not create file for attachment: duplicate filename.');
                case Parser::ATTACHMENT_DUPLICATE_SUFFIX:
                    $attachment_path = $this->suffixFileName($attachment_path);
                    break;
            }
        }

        /** @var resource $fp */
        if ($fp = fopen($attachment_path, 'w')) {
            while ($bytes = $this->read()) {
                fwrite($fp, $bytes);
            }
            fclose($fp);
            return realpath($attachment_path);
        } else {
            throw new Exception('Could not write attachments. Your directory may be unwritable by PHP.');
        }
    }
}
