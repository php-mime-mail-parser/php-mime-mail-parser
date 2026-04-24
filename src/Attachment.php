<?php

namespace PhpMimeMailParser;

class Attachment
{
    private ?string $content = null;

    /**
     * Max duplicate number
     */
    public int $maxDuplicateNumber = 100;

    public function __construct(
        private readonly string $filename,
        private readonly string $contentType,
        private readonly mixed $stream,
        private readonly string $contentDisposition = 'attachment',
        private readonly string $contentId = '',
        private readonly array $headers = [],
        private readonly string $mimePartStr = ''
    ) {
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
     * @throws Exception
     */
    public function save(
        string $attach_dir,
        string $filenameStrategy = Parser::ATTACHMENT_DUPLICATE_SUFFIX
    ): string|false {
        $attach_dir = rtrim($attach_dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        if (!is_dir($attach_dir)) {
            mkdir($attach_dir);
        }

        $attachment_path = match ($filenameStrategy) {
            Parser::ATTACHMENT_RANDOM_FILENAME => $this->generateRandomFilePath($attach_dir),
            Parser::ATTACHMENT_DUPLICATE_THROW,
            Parser::ATTACHMENT_DUPLICATE_SUFFIX => $attach_dir . $this->getFilename(),
            default => throw new Exception('Invalid filename strategy argument provided.')
        };

        if (file_exists($attachment_path)) {
            $attachment_path = match ($filenameStrategy) {
                Parser::ATTACHMENT_DUPLICATE_THROW =>
                    throw new Exception('Could not create file for attachment: duplicate filename.'),
                Parser::ATTACHMENT_DUPLICATE_SUFFIX => $this->suffixFileName($attachment_path),
                default => $attachment_path
            };
        }

        return $this->writeAttachmentToFile($attachment_path);
    }

    private function generateRandomFilePath(string $attach_dir): string
    {
        $fileInfo = pathinfo($this->getFilename());
        $extension = !empty($fileInfo['extension']) ? '.' . $fileInfo['extension'] : '';
        return $attach_dir . bin2hex(random_bytes(16)) . $extension;
    }

    private function writeAttachmentToFile(string $attachment_path): string|false
    {
        if ($fp = fopen($attachment_path, 'w')) {
            while ($bytes = $this->read()) {
                fwrite($fp, $bytes);
            }
            fclose($fp);
            return realpath($attachment_path);
        }

        throw new Exception('Could not write attachments. Your directory may be unwritable by PHP.');
    }
}
