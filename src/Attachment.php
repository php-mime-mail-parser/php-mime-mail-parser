<?php

namespace PhpMimeMailParser;

use PhpMimeMailParser\Contracts\AttachmentInterface;

/**
 * Attachment of php-mime-mail-parser
 *
 */
final class Attachment extends \SplFileInfo implements AttachmentInterface
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
     * @var string $content File Content
     */
    protected $content;

    /**
     * @var string $contentDisposition Content-Disposition (attachment or inline)
     */
    protected $contentDisposition;

    /**
     * @var string $contentId Content-ID
     */
    protected $contentId;

    /**
     * @var array $headers An Array of the attachment headers
     */
    protected $headers;

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

    public function __construct(string $file_name = '')
    {
        parent::__construct($file_name);
    }

    /**
     * Create Attachment.
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
        $stream,
        $mimePartStr = '',
        MimePart $part
    ) {

        $mimeHeaderDecoder  = new MimeHeaderDecoder(new Charset(), new ContentTransferDecoder());

        $filename = $part->getDispositionFileName() ?? $part->getContentName();

        if ($filename) {
            $filename = $mimeHeaderDecoder->decodeHeader($filename);
            $filename = preg_replace('((^\.)|\/|[\n|\r|\n\r]|(\.$))', '_', $filename);
        } elseif (mb_strpos($part->getContentType(), 'message/') !== false) {
            $Parser = new Parser();
            $Parser->setStream($stream);

            if ($Parser->getHeader('subject')) {
                $filename = $Parser->getHeader('subject').'.eml';
                $filename = preg_replace('((^\.)|\/|[\n|\r|\n\r]|(\.$))', '_', $filename);
            }
        }

        $attachment = new self($filename ?? 'noname');
        
        $attachment->stream = $stream;
        $attachment->content = null;
        $attachment->mimePartStr = $mimePartStr;

        $attachment->filename =  $filename ?? 'noname';
        $attachment->contentType = $part->getContentType();
        $attachment->contentDisposition = $part->getContentDisposition();
        $attachment->contentId = $part->getContentId();
        $attachment->headers = $part->getHeaders();
        

        return $attachment;
    }

    /**
     * retrieve the attachment filename
     *
     * @return string
     */
    // public function getFilename(): string
    // {
    //     return $this->filename;
    // }

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
     * @return string
     */
    public function getContentDisposition(): ?string
    {
        return $this->contentDisposition;
    }

    /**
     * Retrieve the Attachment Content-ID
     *
     * @return string
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
        $filenameStrategy = Parser::ATTACHMENT_DUPLICATE_SUFFIX
    ): string {
        $attach_dir = rtrim($attach_dir, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
        if (!is_dir($attach_dir)) {
            mkdir($attach_dir);
        }

        // Determine filename
        switch ($filenameStrategy) {
            case Parser::ATTACHMENT_RANDOM_FILENAME:
                $fileInfo = pathinfo($this->getFilename());
                $extension  = empty($fileInfo['extension']) ? '' : '.'.$fileInfo['extension'];
                $attachment_path = $attach_dir.uniqid().$extension;
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
        if (!$fp = @fopen($attachment_path, 'w')) {
            throw new Exception('Could not write attachments. Your directory may be unwritable by PHP.');
        }

        stream_copy_to_stream($this->stream, $fp);
        fclose($fp);

        return realpath($attachment_path);
    }
}
