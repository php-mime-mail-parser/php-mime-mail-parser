<?php

namespace PhpMimeMailParser;

use PhpMimeMailParser\Contracts\AttachmentInterface;

/**
 * Attachment of php-mime-mail-parser
 *
 * @see \Tests\PhpMimeMailParser\AttachmentTest
 */
final class Attachment implements AttachmentInterface
{
    /**
     * @var string $filename Filename
     */
    protected $filename;

    /**
     * @var string $contentType Mime Type
     */
    protected $contentType;

    /**
     * @var string|null $content File Content
     */
    protected $content;

    /**
     * @var string $contentDisposition Content-Disposition (attachment or inline)
     */
    protected $contentDisposition;

    /**
     * @var string|null $contentId Content-ID
     */
    protected $contentId;

    /**
     * @var array $headers An Array of the attachment headers
     */
    protected $headers = [];

    /**
     * @var resource $stream
     */
    protected $stream;

    /**
     * @var string $mimePartStr
     */
    protected $mimePartStr;

    /**
     * @var integer $maxDuplicateNumber
     */
    public $maxDuplicateNumber = 100;

    public static function create(Entity $part): \PhpMimeMailParser\Attachment
    {
        $attachment = new self();
        
        $attachment->stream = $attachment->createStream($part);
        $attachment->content = null;
        $attachment->mimePartStr = $part->getCompleteBody();

        $mimeHeaderDecoder  = new MimeHeaderDecoder(new Charset(), new ContentTransferDecoder());

        $filename = $part->getDispositionFileName() ?? $part->getContentName();

        if ($filename) {
            $filename = $mimeHeaderDecoder->decodeHeader($filename);
            $filename = preg_replace('((^\.)|\/|[\n|\r|\n\r]|(\.$))', '_', $filename);
        } elseif (mb_strpos($part->getContentType(), 'message/') !== false) {
            $Parser = Parser::fromStream($attachment->stream);

            if ($Parser->getSubject()) {
                $filename = $Parser->getSubject().'.eml';
                $filename = preg_replace('((^\.)|\/|[\n|\r|\n\r]|(\.$))', '_', $filename);
            }
        }

        $attachment->filename =  $filename ?? 'noname';
        $attachment->contentType = $part->getContentType();
        $attachment->contentDisposition = $part->getContentDisposition();
        $attachment->contentId = $part->getContentId();
        $attachment->headers = $part->getHeadersRaw();
        

        return $attachment;
    }

    /**
     * @return resource|bool
     */
    public function createStream($entity)
    {
        $tempFp = tmpfile();
        $entityPart = $entity->getPart();
        $headers = $entityPart['headers'] ?? null;
        $encodingType = $headers['content-transfer-encoding'] ?? '';
        
        // There could be multiple Content-Transfer-Encoding headers, we need only one
        if (is_array($encodingType)) {
            $encodingType = $encodingType[0];
        }
        $ctDecoder = new ContentTransferDecoder();
        fwrite($tempFp, $ctDecoder->decodeContentTransfer($entity->getBody(), $encodingType));
        fseek($tempFp, 0, SEEK_SET);
        
        return $tempFp;
    }

    /**
     * retrieve the attachment filename
     *
     * @return string
     */
    public function getFilename(): string
    {
        return $this->filename;
    }

    /**
     * Retrieve the Attachment Content-Type
     *
     * @return string
     */
    public function getContentType(): string
    {
        return $this->contentType;
    }

    /**
     * Retrieve the Attachment Content-Disposition
     *
     * @return string|null
     */
    public function getContentDisposition(): ?string
    {
        return $this->contentDisposition;
    }

    /**
     * Retrieve the Attachment Content-ID
     *
     * @return string|null
     */
    public function getContentID(): ?string
    {
        return $this->contentId;
    }

    /**
     * Retrieve the Attachment Headers
     *
     * @return array
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Get a handle to the stream
     *
     * @return resource
     */
    public function getStream()
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
    private function suffixFileName(string $fileName): string
    {
        $pathInfo = pathinfo($fileName);
        $dirname = $pathInfo['dirname'].DIRECTORY_SEPARATOR;
        $filename = $pathInfo['filename'];
        $extension  = empty($pathInfo['extension']) ? '' : '.'.$pathInfo['extension'];

        $i = 0;
        do {
            $i++;

            $duplicateExtension = $i > $this->maxDuplicateNumber ? uniqid() : $i;

            $resultName = $dirname.$filename.sprintf('_%s', $duplicateExtension).$extension;
        } while (file_exists($resultName));

        return $resultName;
    }

    /**
     * Retrieve the file content in one go
     *
     * @return string
     */
    public function getContent(): string
    {
        if ($this->content === null) {
            fseek($this->stream, 0);
            $this->content = stream_get_contents($this->stream);
        }

        return $this->content;
    }

    /**
     * Get mime part string for this attachment
     *
     * @return string
     */
    public function getMimePartStr(): string
    {
        return $this->mimePartStr;
    }

    public function save(
        string $attachDir,
        string $filenameStrategy = Parser::ATTACHMENT_DUPLICATE_SUFFIX
    ): string {
        $attachDir = rtrim($attachDir, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
        if (!is_dir($attachDir)) {
            mkdir($attachDir);
        }

        // Determine filename
        switch ($filenameStrategy) {
            case Parser::ATTACHMENT_RANDOM_FILENAME:
                $fileInfo = pathinfo($this->getFilename());
                $extension  = empty($fileInfo['extension']) ? '' : '.'.$fileInfo['extension'];
                $attachmentPath = $attachDir.uniqid().$extension;
                break;
            case Parser::ATTACHMENT_DUPLICATE_THROW:
            case Parser::ATTACHMENT_DUPLICATE_SUFFIX:
                $attachmentPath = $attachDir.$this->getFilename();
                break;
            default:
                throw new Exception('Invalid filename strategy argument provided.');
        }

        // Handle duplicate filename
        if (file_exists($attachmentPath)) {
            if ($filenameStrategy == Parser::ATTACHMENT_DUPLICATE_THROW) {
                throw new Exception('Could not create file for attachment: duplicate filename.');
            } elseif ($filenameStrategy == Parser::ATTACHMENT_DUPLICATE_SUFFIX) {
                $attachmentPath = $this->suffixFileName($attachmentPath);
            }
        }

        if (!$fp = @fopen($attachmentPath, 'w')) {
            throw new Exception('Could not write attachments. Your directory may be unwritable by PHP.');
        }

        stream_copy_to_stream($this->stream, $fp);
        fclose($fp);

        return realpath($attachmentPath);
    }
}
