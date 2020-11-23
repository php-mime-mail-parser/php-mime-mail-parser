<?php

namespace PhpMimeMailParser;

use PhpMimeMailParser\Contracts\ParserInterface;

/**
 * Parser of php-mime-mail-parser
 *
 * A fully tested email parser (mailparse extension wrapper).
 *
 * @see \Tests\PhpMimeMailParser\ParserTest
 */
final class Parser implements ParserInterface
{
    /**
     * PHP MimeParser Resource ID
     *
     * @var resource $resource
     */
    protected $resource;

    /**
     * A file pointer to email
     *
     * @var resource $stream
     */
    protected $stream;

    /**
     * A text of an email
     *
     * @var string $data
     */
    protected $data;

    /**
     * Entities of an email
     *
     * @var array $entities
     */
    protected $entities = [];

    /**
     * @var \PhpMimeMailParser\ParserConfig
     */
    protected $parserConfig;

    protected $linebreakAdded = false;

    /**
     * Valid stream modes for reading
     *
     * @var array
     */
    protected static $readableModes = [
        'r', 'r+', 'w+', 'a+', 'x+', 'c+', 'rb', 'r+b', 'w+b', 'a+b',
        'x+b', 'c+b', 'rt', 'r+t', 'w+t', 'a+t', 'x+t', 'c+t'
    ];

    private function __construct(ParserConfig $parserConfig = null)
    {
        $this->parserConfig = $parserConfig ?? new ParserConfig;
    }

    public static function fromPath(string $path, ParserConfig $config = null): \PhpMimeMailParser\Parser
    {
        $parser = new self($config);
        if (is_writable($path)) {
            $file = fopen($path, 'a+');
            fseek($file, -1, SEEK_END);
            if (fread($file, 1) != "\n") {
                fwrite($file, PHP_EOL);
            }
            fclose($file);
        }

        // should parse message incrementally from file
        $parser->resource = mailparse_msg_parse_file($path);
        $parser->stream = fopen($path, 'r');
        $parser->parse();
        return $parser;
    }

    public static function fromText(string $data, ParserConfig $config = null): \PhpMimeMailParser\Parser
    {
        if (empty($data)) {
            throw new Exception('You must not call fromText with an empty string parameter');
        }

        $parser = new self($config);

        // Adding a trailing line break as a workaround for this bug in PHP mailparse: https://bugs.php.net/bug.php?id=75923
        if (substr($data, -1) != "\n") {
            $data .= PHP_EOL;
            $parser->linebreakAdded = true;
        }

        $parser->resource = mailparse_msg_create();
        // does not parse incrementally, fast memory hog might explode
        mailparse_msg_parse($parser->resource, $data);
        $parser->data = $data;
        $parser->parse();

        return $parser;
    }

    public static function fromStream($stream, ParserConfig $config = null): \PhpMimeMailParser\Parser
    {
        $parser = new self($config);
        // streams have to be cached to file first
        $meta = @stream_get_meta_data($stream);
        if (empty($meta) || !$meta['mode'] || !in_array($meta['mode'], self::$readableModes, true)) {
            throw new Exception(
                'setStream() expects parameter stream to be readable stream resource.'
            );
        }

        $tmpFp = self::tmpfile();

        while (!feof($stream)) {
            fwrite($tmpFp, fread($stream, 2028));
        }

        if (fread($tmpFp, 1) != "\n") {
            fwrite($tmpFp, PHP_EOL);
        }

        fseek($tmpFp, 0);
        $parser->stream = &$tmpFp;

        fclose($stream);

        $parser->resource = mailparse_msg_create();
        // parses the message incrementally (low memory usage but slower)
        while (!feof($parser->stream)) {
            mailparse_msg_parse($parser->resource, fread($parser->stream, 2082));
        }
        $parser->parse();
        return $parser;
    }

    /**
     * Free the held resources
     *
     * @return void
     */
    public function __destruct()
    {
        // clear the email file resource
        if (is_resource($this->stream)) {
            fclose($this->stream);
        }
        // clear the MailParse resource
        if (is_resource($this->resource)) {
            mailparse_msg_free($this->resource);
        }
    }

    /**
     * @return resource
     * @throws Exception
     */
    private static function tmpfile()
    {
        if ($tmpFp = tmpfile()) {
            return $tmpFp;
        }

        throw new Exception(
            'Could not create temporary files for attachments. Your tmp directory may be unwritable by PHP.'
        );
    }

    /**
     * Parse the Message into entities
     */
    private function parse(): void
    {
        $structure = mailparse_msg_get_structure($this->resource);
        $this->entities = [];

        foreach ($structure as $entityId) {
            $part = mailparse_msg_get_part($this->resource, $entityId);
            $partData = mailparse_msg_get_part_data($part);
            $entity = new Entity($entityId, $partData, $this->stream, $this->data, $this->parserConfig);
            $this->entities[$entityId] = $entity->parse();
        }
    }

    /**
     * @return mixed[]
     */
    public function getEntities(): array
    {
        return $this->entities;
    }

    /**
     * Retrieve all mail headers
     *
     * @return array
     * @throws Exception
     */
    public function getHeaders(): array
    {
        return $this->entities[1]->getHeaders();
    }

    /**
     * Retrieve the raw mail headers as a string
     *
     * @return array
     * @throws Exception
     */
    public function getHeadersRaw(): array
    {
        return $this->entities[1]->getHeadersRaw();
    }

    /**
     * Checks whether a given entity ID is a child of another entity
     * eg. an RFC822 attachment may have one or more text entity
     *
     * @param string $entityId
     * @param string $parentEntityId
     */
    private function entityIdIsChildOfEntity(string $entityId, string $parentEntityId): bool
    {
        $parentEntityId .= '.';
        return substr($entityId, 0, strlen($parentEntityId)) === $parentEntityId;
    }

    /**
     * Whether the given entity ID is a child of any attachment entity in the message.
     *
     * @param string $checkEntityId
     */
    private function entityIdIsChildOfAnAttachment(string $checkEntityId): bool
    {
        foreach ($this->entities as $entityId => $entity) {
            if ($entity->getContentDisposition() == 'attachment' && $this->entityIdIsChildOfEntity($checkEntityId, $entityId)) {
                return true;
            }
        }
        return false;
    }

    public function getHeader(string $name)
    {
        return $this->entities[1]->getHeader($name);
    }

    public function getHeaderRaw(string $name): string
    {
        return $this->entities[1]->getHeaderRaw($name);
    }

    /**
     * @return mixed[]|bool|string
     */
    public function getSubject()
    {
        return $this->getHeader('subject');
    }

    public function getSubjectRaw()
    {
        return $this->getHeaderRaw('subject');
    }

    /**
     * @return mixed[]|bool|string
     */
    public function getFrom()
    {
        return $this->getHeader('from');
    }

    public function getFromRaw()
    {
        return $this->getHeaderRaw('from');
    }

    public function getAddressesFrom()
    {
        return $this->entities[1]->getAddresses('from');
    }

    public function getAddressesFromRaw()
    {
        return $this->entities[1]->getAddresses('from');
    }
    public function getTo()
    {
        return $this->getHeader('to');
    }

    public function getToRaw()
    {
        return $this->getHeaderRaw('to');
    }

    public function getAddressesTo()
    {
        return $this->entities[1]->getAddresses('to');
    }

    public function getAddressesToRaw()
    {
        return $this->entities[1]->getAddresses('to');
    }

    public function getAddresses(string $name): array
    {
        return $this->entities[1]->getAddresses($name);
    }

    public function getAddressesRaw($name)
    {
        return $this->entities[1]->getAddresses($name);
    }


    /**
     * @return mixed[]
     */
    public function getMessageBodies($subTypes): array
    {
        $entities = $this->filterEntities($subTypes, false);

        $bodies = [];
        foreach ($entities as $entity) {
            $bodies[] = $entity->decoded();
        }
        return $bodies;
    }

    /**
     * @return mixed[]
     */
    public function getMessageBodiesRaw($subTypes): array
    {
        $entities = $this->filterEntities($subTypes, false);

        $bodies = [];
        foreach ($entities as $entity) {
            $bodies[] = $entity->getBody();
        }
        return $bodies;
    }

    public function getText(): string
    {
        return $this->getMessageBodies(['text'])[0] ?? '';
    }

    public function getTextRaw(): string
    {
        return $this->getMessageBodiesRaw(['text'])[0] ?? '';
    }

    public function getHtmlNotEmbedded(): string
    {
        return $this->getMessageBodies(['html'])[0] ?? '';
    }

    public function getHtml(): string
    {
        $text = $this->getHtmlNotEmbedded();

        $attachments = $this->getInlineAttachments();
        foreach ($attachments as $attachment) {
            if (!empty($attachment->getContentID())) {
                $text = str_replace(
                    '"cid:'.$attachment->getContentID().'"',
                    '"'.$this->getEmbeddedData($attachment->getContentID()).'"',
                    $text
                );
            }
        }
        return $text;
    }

    public function getHtmlRaw(): string
    {
        return $this->getMessageBodiesRaw(['html'])[0] ?? '';
    }

    /**
     * Returns the embedded data structure
     *
     * @param string $contentId Content-Id
     */
    private function getEmbeddedData(string $contentId): string
    {
        $embeddedData = 'data:';

        foreach ($this->entities as $entity) {
            if ($entity->getContentId() == $contentId) {
                $embeddedData .= $entity->getContentType();
                $embeddedData .= ';'.$entity->getContentTransferEncoding();
                $embeddedData .= ','.$entity->getBody();
                break;
            }
        }
        return $embeddedData;
    }

    /**
     * @return mixed[]
     */
    public function filterEntities($filters, $includeSubEntities = true): array
    {
        $filteredEntities = [];

        foreach ($this->entities as $entityId => $entity) {
            $disposition = $entity->getContentDisposition();
            $contentType = $entity->getContentType();
            $attachmentType = null;

            if (isset($disposition)) {
                $attachmentType = $disposition == 'inline' || $disposition == 'attachment' ? $disposition : 'attachment';
            } elseif ($contentType != 'text/plain'
            && $contentType != 'text/html'
            && $contentType!= 'multipart/alternative'
            && $contentType != 'multipart/related'
            && $contentType != 'multipart/mixed'
            && $contentType != 'text/plain; (error)') {
                $attachmentType = 'attachment';
            }
            if ($this->entityIdIsChildOfAnAttachment($entityId) && !$includeSubEntities) {
                continue;
            }

            if ($entity->isTextMessage('plain')) {
                if (\in_array('text', $filters)) {
                    $filteredEntities[$entityId] = $entity;
                    continue;
                }
            } elseif ($entity->isTextMessage('html')) {
                if (\in_array('html', $filters)) {
                    $filteredEntities[$entityId] = $entity;
                    continue;
                }
            } elseif ($attachmentType == 'inline') {
                if (\in_array('inline', $filters)) {
                    $filteredEntities[$entityId] = $entity;
                    continue;
                }
            } elseif ($attachmentType == 'attachment') {
                if (\in_array('attachment', $filters)) {
                    $filteredEntities[$entityId] = $entity;
                    continue;
                }
            } elseif ($attachmentType == null) {
                continue;
            }
        }
        return $filteredEntities;
    }

    /**
     * @return mixed[]
     */
    private function createAttachmentsFromEntities($contentDispositions, $includeSubEntities): array
    {
        $attachments = [];

        $entities = $this->filterEntities($contentDispositions, $includeSubEntities);

        foreach ($entities  as $entity) {
            $a = $this->parserConfig->getAttachmentInterface();
            $attachments[] = $a::create($entity);
        }

        return $attachments;
    }

    public function getAttachments()
    {
        return $this->getTopLevelAttachments(['attachment']);
    }

    public function getInlineAttachments()
    {
        return $this->getTopLevelAttachments(['inline']);
    }

    public function getTopLevelAttachments($contentDisposition)
    {
        return $this->createAttachmentsFromEntities($contentDisposition, false);
    }

    public function getNestedAttachments($contentDisposition)
    {
        return $this->createAttachmentsFromEntities($contentDisposition, true);
    }

    /**
     * @return mixed[]
     */
    public function saveNestedAttachments($directory, $contentDisposition): array
    {
        $attachmentsPaths = [];

        foreach ($this->getNestedAttachments($contentDisposition) as $attachment) {
            $attachmentsPaths[] = $attachment->save($directory, $this->parserConfig->getFilenameStrategy());
        }

        return $attachmentsPaths;
    }

    /**
     * Retrieve the resource
     *
     * @return resource resource
     */
    public function getResource()
    {
        return $this->resource;
    }

    /**
     * Retrieve the file pointer to email
     *
     * @return resource stream
     */
    public function getStream()
    {
        return $this->stream;
    }

    /**
     * Retrieve the text of an email
     *
     * @return string|null data
     */
    public function getData(): ?string
    {
        return $this->data;
    }
}
