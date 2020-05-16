<?php

namespace PhpMimeMailParser;

use PhpMimeMailParser\Contracts\CharsetManager;
use PhpMimeMailParser\Contracts\MimeHeaderEncodingManager;
use PhpMimeMailParser\Contracts\ContentTransferEncodingManager;
use PhpMimeMailParser\Contracts\ParserInterface;
use PhpMimeMailParser\Contracts\AttachmentInterface;

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
     * Parts of an email
     *
     * @var array $parts
     */
    protected $parts;

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
    public function __construct(
        CharsetManager $charset = null,
        ContentTransferEncodingManager $ctDecoder = null,
        MimeHeaderEncodingManager $headerDecoder = null,
        AttachmentInterface $attachmentInterface = null
    ) {
        $this->charset = $charset ?? new Charset();
        $this->ctDecoder = $ctDecoder ?? new ContentTransferDecoder();
        $this->headerDecoder = $headerDecoder ?? new MimeHeaderDecoder($this->charset, $this->ctDecoder);
        $this->attachmentInterface = $attachmentInterface ?? new Attachment();

        $this->middlewareStack = new MiddlewareStack();
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
     * Parse the Message into parts
     *
     * @return void
     */
    private function parse()
    {
        $structure = mailparse_msg_get_structure($this->resource);
        $this->parts = [];
        foreach ($structure as $part_id) {
            $part = mailparse_msg_get_part($this->resource, $part_id);
            $part_data = mailparse_msg_get_part_data($part);
            $mimePart = new MimePart($part_id, $part_data);
            // let each middleware parse the part before saving
            $this->parts[$part_id] = $this->middlewareStack->parse($mimePart)->getPart();
        }
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
        if (!isset($this->parts[1])) {
            throw new Exception(
                'setPath() or setText() or setStream() must be called before retrieving email headers.'
            );
        }

        $headers = $this->getPart('headers', $this->parts[1]);
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
        if (!isset($this->parts[1])) {
            throw new Exception(
                'setPath() or setText() or setStream() must be called before retrieving email headers.'
            );
        }

        $headers = $this->getPart('headers', $this->parts[1]);

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
        if (!isset($this->parts[1])) {
            throw new Exception(
                'setPath() or setText() or setStream() must be called before retrieving email headers.'
            );
        }

        return $this->getPartHeader($this->parts[1]);
    }

    /**
     * Retrieve the raw Header of a MIME part
     *
     * @return String
     * @param $part Object
     * @throws Exception
     */
    private function getPartHeader(&$part)
    {
        return $this->getSection($part['starting-pos'], $part['starting-pos-body']);
    }

    /**
     * Checks whether a given part ID is a child of another part
     * eg. an RFC822 attachment may have one or more text parts
     *
     * @param string $partId
     * @param string $parentPartId
     * @return bool
     */
    private function partIdIsChildOfPart($partId, $parentPartId)
    {
        $parentPartId = $parentPartId.'.';
        return substr($partId, 0, strlen($parentPartId)) == $parentPartId;
    }

    /**
     * Whether the given part ID is a child of any attachment part in the message.
     *
     * @param string $checkPartId
     * @return bool
     */
    private function partIdIsChildOfAnAttachment($checkPartId)
    {
        foreach ($this->parts as $partId => $part) {
            if ($this->getPart('content-disposition', $part) == 'attachment') {
                if ($this->partIdIsChildOfPart($checkPartId, $partId)) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Returns the email message body in the specified format
     *
     * @param string $type text, html or htmlEmbedded
     *
     * @return string Body
     * @throws Exception
     */
    public function getMessageBody($type = 'text')
    {
        $mime_types = [
            'text'         => 'text/plain',
            'html'         => 'text/html',
            'htmlEmbedded' => 'text/html',
        ];

        if (!array_key_exists($type, $mime_types)) {
            throw new Exception(
                'Invalid type specified for getMessageBody(). Expected: text, html or htmlEmbeded.'
            );
        }

        $part_type = $type === 'htmlEmbedded' ? 'html' : $type;
        $inline_parts = $this->getInlineParts($part_type);
        $body = empty($inline_parts) ? '' : $inline_parts[0];

        if ($type == 'htmlEmbedded') {
            $attachments = $this->getAttachments();
            foreach ($attachments as $attachment) {
                if ($attachment->getContentID() != '') {
                    $body = str_replace(
                        '"cid:'.$attachment->getContentID().'"',
                        '"'.$this->getEmbeddedData($attachment->getContentID()).'"',
                        $body
                    );
                }
            }
        }

        return $body;
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

        foreach ($this->parts as $part) {
            if ($this->getPart('content-id', $part) == $contentId) {
                $embeddedData .= $this->getPart('content-type', $part);
                $embeddedData .= ';'.$this->getPart('transfer-encoding', $part);
                $embeddedData .= ','.$this->getPartBody($part);
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

    /**
     * Returns the attachments contents in order of appearance
     *
     * @return Attachment[]
     */
    public function getInlineParts(string $type = 'text'): array
    {
        if ($type != 'text' && $type != 'html') {
            throw new Exception('Invalid type specified for getInlineParts(). "type" can either be text or html.');
        }

        $parts = $this->filterParts([$type], false);
        $inline_parts = [];

        foreach ($parts as $part) {
            $encodingType = $this->getPart('transfer-encoding', $part);
            $undecodedBody = $this->ctDecoder->decodeContentTransfer($this->getPartBody($part), $encodingType);
            $inline_parts[] = $this->charset->decodeCharset($undecodedBody, $this->getPartCharset($part));
        }

        return $inline_parts;
    }

    public function isTextMessage($part, $subType)
    {
        $disposition = $this->getPart('content-disposition', $part);
        $contentType = $this->getPart('content-type', $part);

        if ($disposition == 'inline' || empty($disposition))
        {
            if ($contentType == 'text/'.$subType)
            {
                return true;
            }
        }
        return false;
    }

    public function filterParts($filters, $includeSubParts = true)
    {
        $filteredParts = [];

        foreach ($this->parts as $partId => $part) {

            $disposition = $this->getPart('content-disposition', $part);
            $contentType = $this->getPart('content-type', $part);
            $attachmentType = null;

            if (isset($disposition))
            {
                if ($disposition == 'inline' || $disposition == 'attachment')
                {
                    $attachmentType = $disposition;
                }
                else {
                    $attachmentType = 'attachment';
                }
                
            }
            else {
                if (
                    $contentType != 'text/plain' 
                    && $contentType != 'text/html'
                    && $contentType!= 'multipart/alternative'
                    && $contentType != 'multipart/related'
                    && $contentType != 'multipart/mixed'
                    && $contentType != 'text/plain; (error)')
                {
                    $attachmentType = 'attachment';
                }
            }
            if ($this->partIdIsChildOfAnAttachment($partId) && !$includeSubParts) {
                continue;
            }

            if ($this->isTextMessage($part, 'plain'))
            {
                if (\in_array('text', $filters)){
                    $filteredParts[] = $part;
                    continue;
                }
            }
            elseif ($this->isTextMessage($part, 'html'))
            {
                if (\in_array('html', $filters)){
                    $filteredParts[] = $part;
                    continue;
                }
            }
            elseif ($attachmentType == 'inline')
            {
                if (\in_array('inline', $filters)){
                    $filteredParts[] = $part;
                    continue;
                }
            }
            elseif ($attachmentType == 'attachment')
            {
                if (\in_array('attachment', $filters)){
                    $filteredParts[] = $part;
                    continue;
                }
            }
            elseif ($attachmentType == null)
            {
                continue;
            }
        }
        return $filteredParts;
    }

    /**
     * Returns the attachments contents in order of appearance
     *
     * @return Attachment[]
     */
    public function getAttachments($attachment_types = self::GA_INCLUDE_ALL)
    {
        $includeSubParts = ($attachment_types & self::GA_INCLUDE_NESTED) || is_bool($attachment_types);

        $attachments = [];
        $nonameIter = 0;

        $filters = ['attachment'];
        if ( $attachment_types & self::GA_INCLUDE_INLINE ) {
            $filters[] = 'inline';
        }

        $parts = $this->filterParts($filters, $includeSubParts);

        foreach ($parts as $part) {

            $filename = $this->getPart('disposition-filename', $part) ?? $this->getPart('content-name', $part);

            if (isset($filename))
            {
                $filename = $this->headerDecoder->decodeHeader($filename);
                $filename = preg_replace('((^\.)|\/|[\n|\r|\n\r]|(\.$))', '_', $filename);
            }
            else {
                $nonameIter++;
                $filename = 'noname'.$nonameIter;
            }

            $attachments[] = new Attachment(
                $filename,
                $this->getPart('content-type', $part),
                $this->getAttachmentStream($part),
                $this->getPart('content-disposition', $part),
                $this->getPart('content-id', $part),
                $this->getPart('headers', $part),
                $this->getPartComplete($part),
                new MimePart(1,$part)
            );
        }

        return $attachments;
    }

    /**
     * Save attachments in a folder
     *
     * @param string $attach_dir directory
     * @param bool $include_inline
     * @param string $filenameStrategy How to generate attachment filenames
     *
     * @return array Saved attachments paths
     * @throws Exception
     */
    public function saveAttachments(
        $attach_dir,
        $include_inline = true,
        $filenameStrategy = self::ATTACHMENT_DUPLICATE_SUFFIX
    ) {
        $attachments = $this->getAttachments($include_inline);

        $attachments_paths = [];
        foreach ($attachments as $attachment) {
            $attachments_paths[] = $attachment->save($attach_dir, $filenameStrategy);
        }

        return $attachments_paths;
    }

    /**
     * Read the attachment Body and save temporary file resource
     *
     * @param array $part
     *
     * @return resource Mime Body Part
     * @throws Exception
     */
    private function getAttachmentStream(&$part)
    {
        $temp_fp = self::tmpfile();

        $headers = $this->getPart('headers', $part);
        $encodingType = array_key_exists('content-transfer-encoding', $headers) ?
            $headers['content-transfer-encoding'] : '';

        // There could be multiple Content-Transfer-Encoding headers, we need only one
        if (is_array($encodingType)) {
            $encodingType = $encodingType[0];
        }

        fwrite($temp_fp, $this->ctDecoder->decodeContentTransfer($this->getPartBody($part), $encodingType));
        fseek($temp_fp, 0, SEEK_SET);

        return $temp_fp;
    }

    /**
     * Return the charset of the MIME part
     *
     * @param array $part
     *
     * @return string
     */
    private function getPartCharset($part)
    {
        return $this->charset->getCharsetAlias($part['charset']);
    }

    /**
     * Retrieve a specified MIME part
     *
     * @param string $type
     * @param array  $parts
     *
     * @return string|array|null
     */
    private function getPart($type, &$parts)
    {
        if (array_key_exists($type, $parts)) {
            return $parts[$type];
        }

        return null;
    }

    /**
     * Retrieve the Body of a MIME part
     *
     * @param array $part
     *
     * @return string
     */
    private function getPartBody(&$part)
    {
        return $this->getSection($part['starting-pos-body'], $part['ending-pos-body']);
    }

    /**
     * Retrieve the content of a MIME part
     *
     * @param array $part
     *
     * @return string
     */
    private function getPartComplete(&$part)
    {
        return $this->getSection($part['starting-pos'], $part['ending-pos']);
    }

    private function getSection($start, $end): string
    {
        if ($start >= $end) {
            return '';
        }

        if ($this->stream) {
            fseek($this->stream, $start, SEEK_SET);

            return fread($this->stream, $end - $start);
        }

        return substr($this->data, $start, $end - $start);
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
     * Retrieve the parts of an email
     *
     * @return array parts
     */
    public function getParts()
    {
        return $this->parts;
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
