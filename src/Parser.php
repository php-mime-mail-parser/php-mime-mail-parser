<?php

namespace PhpMimeMailParser;

use PhpMimeMailParser\Contracts\AttachmentInterface;
use PhpMimeMailParser\Contracts\CharsetManager;
use PhpMimeMailParser\Contracts\ContentTransferEncodingManager;
use PhpMimeMailParser\Contracts\MimeHeaderEncodingManager;
use PhpMimeMailParser\Contracts\ParserInterface;

/**
 * Parser of php-mime-mail-parser
 *
 * Fully Tested Mailparse Extension Wrapper for PHP 5.4+
 *
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
    protected $entities;

    /**
     * @var CharsetManager object
     */
    protected $charset;

    /**
     * @var ContentTransferEncodingManager
     */
    private $ctDecoder;

    /**
     * @var MimeHeaderEncodingManager
     */
    private $headerDecoder;

    /**
     * Valid stream modes for reading
     *
     * @var array
     */
    protected static $readableModes = [
        'r', 'r+', 'w+', 'a+', 'x+', 'c+', 'rb', 'r+b', 'w+b', 'a+b',
        'x+b', 'c+b', 'rt', 'r+t', 'w+t', 'a+t', 'x+t', 'c+t'
    ];

    /**
     * Stack of middleware registered to process data
     *
     * @var MiddlewareStack
     */
    protected $middlewareStack;

    /**
     * @var AttachmentInterface
     */
    protected $attachmentInterface;

    /**
     * Parser constructor.
     *
     * @param CharsetManager|null $charset
     */
    public function __construct()
    {
        $this->charset = new Charset();
        $this->ctDecoder = new ContentTransferDecoder();
        $this->headerDecoder = new MimeHeaderDecoder($this->charset, $this->ctDecoder);
        $this->attachmentInterface = new Attachment();

        $this->middlewareStack = new MiddlewareStack();
    }

    public function setCharsetManager($charsetManager)
    {
        $this->charset = $charsetManager;
    }

    public function setContentTransferEncodingManager($contentTransferEncodingManager)
    {
        $this->ctDecoder = $contentTransferEncodingManager;
    }

    public function setMimeHeaderEncodingManager($mimeHeaderEncodingManager)
    {
        $this->headerDecoder = $mimeHeaderEncodingManager;
    }

    public function setAttachmentInterface($attachmentInterface)
    {
        $this->attachmentInterface = $attachmentInterface;
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
     * Set the file path we use to get the email text
     *
     * @param string $path File path to the MIME mail
     *
     * @return Parser MimeMailParser Instance
     */
    public function setPath(string $path): ParserInterface
    {
        if (is_writable($path)) {
            $file = fopen($path, 'a+');
            fseek($file, -1, SEEK_END);
            if (fread($file, 1) != "\n") {
                fwrite($file, PHP_EOL);
            }
            fclose($file);
        }

        // should parse message incrementally from file
        $this->resource = mailparse_msg_parse_file($path);
        $this->stream = fopen($path, 'r');
        $this->parse();

        return $this;
    }

    /**
     * Set the Stream resource we use to get the email text
     *
     * @param resource $stream
     *
     * @return Parser MimeMailParser Instance
     * @throws Exception
     */
    public function setStream($stream): ParserInterface
    {
        // streams have to be cached to file first
        $meta = @stream_get_meta_data($stream);
        if (!$meta || !$meta['mode'] || !in_array($meta['mode'], self::$readableModes, true) || $meta['eof']) {
            throw new Exception(
                'setStream() expects parameter stream to be readable stream resource.'
            );
        }

        $tmp_fp = self::tmpfile();

        while (!feof($stream)) {
            fwrite($tmp_fp, fread($stream, 2028));
        }

        if (fread($tmp_fp, 1) != "\n") {
            fwrite($tmp_fp, PHP_EOL);
        }

        fseek($tmp_fp, 0);
        $this->stream = &$tmp_fp;

        fclose($stream);

        $this->resource = mailparse_msg_create();
        // parses the message incrementally (low memory usage but slower)
        while (!feof($this->stream)) {
            mailparse_msg_parse($this->resource, fread($this->stream, 2082));
        }
        $this->parse();

        return $this;
    }

    /**
     * @return resource
     * @throws Exception
     */
    private static function tmpfile()
    {
        if ($tmp_fp = tmpfile()) {
            return $tmp_fp;
        }

        throw new Exception(
            'Could not create temporary files for attachments. Your tmp directory may be unwritable by PHP.'
        );
    }

    /**
     * Set the email text
     *
     * @param string $data
     *
     * @return Parser MimeMailParser Instance
     */
    public function setText($data): ParserInterface
    {
        if (empty($data)) {
            throw new Exception('You must not call MimeMailParser::setText with an empty string parameter');
        }

        if (substr($data, -1) != "\n") {
            $data = $data.PHP_EOL;
        }

        $this->resource = mailparse_msg_create();
        // does not parse incrementally, fast memory hog might explode
        mailparse_msg_parse($this->resource, $data);
        $this->data = $data;
        $this->parse();

        return $this;
    }

    /**
     * Parse the Message into entities
     *
     * @return void
     */
    private function parse()
    {
        $structure = mailparse_msg_get_structure($this->resource);
        $this->entities = [];
        foreach ($structure as $entityId) {
            $part = mailparse_msg_get_part($this->resource, $entityId);
            $partData = mailparse_msg_get_part_data($part);
            $mimePart = new MimePart($entityId, $partData, $this->stream, $this->data);
            $mimePart->setCharsetManager($this->charset);
            $mimePart->setContentTransferEncodingManager($this->ctDecoder);
            $this->entities[$entityId] = $this->middlewareStack->parse($mimePart);
        }
    }

    public function getEntities()
    {
        return $this->entities;
    }

    /**
     * Retrieve a specific Email Header, without charset conversion.
     *
     * @param string $name Header name (case-insensitive)
     *
     * @return string[]|null
     * @throws Exception
     */
    public function getRawHeader($name): ?array
    {
        if (!isset($this->entities[1])) {
            throw new Exception(
                'setPath() or setText() or setStream() must be called before retrieving email headers.'
            );
        }

        $headers = $this->entities[1]->getHeaders();
        $name = strtolower($name);

        if (array_key_exists($name, $headers)) {
            return (array) $headers[$name];
        }

        return null;
    }

    /**
     * Retrieve a specific Email Header
     *
     * @param string $name Header name (case-insensitive)
     *
     * @return string|array|bool
     */
    public function getHeader($name)
    {
        $rawHeader = $this->getRawHeader($name);

        if ($rawHeader === null) {
            // TODO This should be returning null if we want to have this function to return an optional value
            return false;
        }

        return $this->headerDecoder->decodeHeader($rawHeader[0]);
    }

    /**
     * Retrieve all mail headers
     *
     * @return array
     * @throws Exception
     */
    public function getHeaders(): array
    {
        if (!isset($this->entities[1])) {
            throw new Exception(
                'setPath() or setText() or setStream() must be called before retrieving email headers.'
            );
        }

        $headers = $this->entities[1]->getHeaders();

        array_walk_recursive($headers, function (&$value) {
            $value = $this->headerDecoder->decodeHeader($value);
        });

        return $headers;
    }

    /**
     * Retrieve the raw mail headers as a string
     *
     * @return string
     * @throws Exception
     */
    public function getHeadersRaw()
    {
        if (!isset($this->entities[1])) {
            throw new Exception(
                'setPath() or setText() or setStream() must be called before retrieving email headers.'
            );
        }

        return $this->entities[1]->getBody();
    }

    /**
     * Checks whether a given entity ID is a child of another entity
     * eg. an RFC822 attachment may have one or more text entity
     *
     * @param string $entityId
     * @param string $parentEntityId
     * @return bool
     */
    private function entityIdIsChildOfEntity($entityId, $parentEntityId)
    {
        $parentEntityId = $parentEntityId.'.';
        return substr($entityId, 0, strlen($parentEntityId)) == $parentEntityId;
    }

    /**
     * Whether the given entity ID is a child of any attachment entity in the message.
     *
     * @param string $checkEntityId
     * @return bool
     */
    private function entityIdIsChildOfAnAttachment($checkEntityId)
    {
        foreach ($this->entities as $entityId => $entity) {
            if ($entity->getContentDisposition() == 'attachment') {
                if ($this->entityIdIsChildOfEntity($checkEntityId, $entityId)) {
                    return true;
                }
            }
        }
        return false;
    }

    public function getMessageBodies($subTypes)
    {
        $entities = $this->filterEntities($subTypes, false);

        $bodies = [];
        foreach ($entities as $entity) {
            $bodies[] = $entity->decoded();
        }
        return $bodies;
    }

    public function getMessageBodiesRaw($subTypes)
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
     *
     * @return string
     */
    private function getEmbeddedData(string $contentId)
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
     * Return an array with the following keys display, address, is_group
     *
     * @param string $name Header name (case-insensitive)
     *
     * @return array
     */
    public function getAddresses($name)
    {
        $value = $this->getRawHeader($name)[0];

        $addresses = mailparse_rfc822_parse_addresses($value);

        foreach ($addresses as $i => $item) {
            $addresses[$i]['display'] = $this->headerDecoder->decodeHeader($item['display']);
        }

        return $addresses;
    }

    public function filterEntities($filters, $includeSubEntities = true)
    {
        $filteredEntities = [];

        foreach ($this->entities as $entityId => $entity) {
            $disposition = $entity->getContentDisposition();
            $contentType = $entity->getContentType();
            $attachmentType = null;

            if (isset($disposition)) {
                if ($disposition == 'inline' || $disposition == 'attachment') {
                    $attachmentType = $disposition;
                } else {
                    $attachmentType = 'attachment';
                }
            } else {
                if (
                    $contentType != 'text/plain'
                    && $contentType != 'text/html'
                    && $contentType!= 'multipart/alternative'
                    && $contentType != 'multipart/related'
                    && $contentType != 'multipart/mixed'
                    && $contentType != 'text/plain; (error)') {
                    $attachmentType = 'attachment';
                }
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

    private function createAttachmentsFromEntities($contentDispositions, $includeSubEntities)
    {
        $attachments = [];

        $entities = $this->filterEntities($contentDispositions, $includeSubEntities);

        foreach ($entities  as $entityId => $entity) {
            $attachments[] = $this->attachmentInterface::create($entity);
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

    public function saveNestedAttachments($directory, $contentDisposition, $filenameStrategy = self::ATTACHMENT_DUPLICATE_SUFFIX)
    {
        $attachments_paths = [];

        foreach ($this->getNestedAttachments($contentDisposition) as $attachment) {
            $attachments_paths[] = $attachment->save($directory, $filenameStrategy);
        }

        return $attachments_paths;
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
    public function getData()
    {
        return $this->data;
    }

    /**
     * Retrieve the charset manager object
     *
     * @return CharsetManager charset
     */
    public function getCharset(): CharsetManager
    {
        return $this->charset;
    }

    /**
     * Add a middleware to the parser MiddlewareStack
     * Each middleware is invoked when:
     *   a MimePart is retrieved by mailparse_msg_get_part_data() during $this->parse()
     * The middleware will receive MimePart $part and the next MiddlewareStack $next
     *
     * Eg:
     *
     * $Parser->addMiddleware(function(MimePart $part, MiddlewareStack $next) {
     *      // do something with the $part
     *      return $next($part);
     * });
     *
     * @param callable $middleware Plain Function or Middleware Instance to execute
     * @return void
     */
    public function addMiddleware(callable $middleware): void
    {
        if (!$middleware instanceof Middleware) {
            $middleware = new Middleware($middleware);
        }
        $this->middlewareStack = $this->middlewareStack->add($middleware);
    }
}
