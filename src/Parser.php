<?php

namespace PhpMimeMailParser;

use PhpMimeMailParser\Contracts\CharsetManager;

/**
 * Parser of php-mime-mail-parser
 *
 * Fully Tested Mailparse Extension Wrapper for PHP 5.4+
 *
 */
class Parser
{
    /**
     * Attachment filename argument option for ->saveAttachments().
     */
    const ATTACHMENT_DUPLICATE_THROW  = 'DuplicateThrow';
    const ATTACHMENT_DUPLICATE_SUFFIX = 'DuplicateSuffix';
    const ATTACHMENT_RANDOM_FILENAME  = 'RandomFilename';

    /**
     * PHP MimeParser Resource ID
     */
    protected mixed $resource = null;

    /**
     * A file pointer to email
     */
    protected mixed $stream = null;

    /**
     * A text of an email
     */
    protected string $data = '';

    /**
     * Parts of an email
     */
    protected array $parts = [];

    /**
     * Charset manager instance
     */
    protected CharsetManager $charset;

    /**
     * Valid stream modes for reading
     */
    protected static array $readableModes = [
        'r', 'r+', 'w+', 'a+', 'x+', 'c+', 'rb', 'r+b', 'w+b', 'a+b',
        'x+b', 'c+b', 'rt', 'r+t', 'w+t', 'a+t', 'x+t', 'c+t'
    ];

    /**
     * Stack of middleware registered to process data
     */
    protected MiddlewareStack $middlewareStack;

    /**
     * Parser constructor.
     *
     * @param CharsetManager|null $charset Optional charset manager instance
     */
    public function __construct(?CharsetManager $charset = null)
    {
        $this->charset = $charset ?? new Charset();
        $this->middlewareStack = new MiddlewareStack();
    }

    /**
     * Free the held resources
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
     * @return self
     * @throws Exception
     */
    public function setPath(string $path): self
    {
        if (is_writable($path)) {
            $file = fopen($path, 'a+');
            fseek($file, -1, SEEK_END);
            if (fread($file, 1) !== "\n") {
                fwrite($file, PHP_EOL);
            }
            fclose($file);
        }

        // should parse message incrementally from file
        $this->resource = mailparse_msg_parse_file($path);
        if ($this->resource === false) {
            throw new Exception('MIME message cannot be parsed');
        }

        $this->stream = fopen($path, 'r');
        $this->parse();

        return $this;
    }

    /**
     * Set the Stream resource we use to get the email text
     *
     * @param mixed $stream A readable stream resource
     * @return self
     * @throws Exception
     */
    public function setStream(mixed $stream): self
    {
        // streams have to be cached to file first
        $meta = @stream_get_meta_data($stream);
        // Use null-safe operator to simplify validation (PHP 8.0+)
        $mode = $meta['mode'] ?? null;

        if (!$mode || !in_array($mode, self::$readableModes, true)) {
            throw new Exception(
                'setStream() expects parameter stream to be readable stream resource.'
            );
        }

        /** @var resource $tmp_fp */
        $tmp_fp = tmpfile();
        if ($tmp_fp) {
            while (!feof($stream)) {
                fwrite($tmp_fp, fread($stream, 2028));
            }

            if (fread($tmp_fp, 1) !== "\n") {
                fwrite($tmp_fp, PHP_EOL);
            }

            fseek($tmp_fp, 0);
            $this->stream = &$tmp_fp;
        } else {
            throw new Exception(
                'Could not create temporary files for attachments. Your tmp directory may be unwritable by PHP.'
            );
        }
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
     * Set the email text
     *
     * @param string $data The email data
     * @return self
     * @throws Exception
     */
    public function setText(string $data): self
    {
        if (empty($data)) {
            throw new Exception('You must not call MimeMailParser::setText with an empty string parameter');
        }

        // Use str_ends_with() instead of substr() (PHP 8.0+)
        if (!str_ends_with($data, "\n")) {
            $data .= PHP_EOL;
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
     */
    protected function parse(): void
    {
        if (!$this->resource) {
            throw new Exception(
                'MIME message cannot be parsed'
            );
        }
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
     * @throws Exception
     */
    public function getRawHeader(string $name): string|array|false
    {
        $name = strtolower($name);
        if (isset($this->parts[1])) {
            $headers = $this->getPart('headers', $this->parts[1]);

            return isset($headers[$name]) ? $headers[$name] : false;
        } else {
            throw new Exception(
                'setPath() or setText() or setStream() must be called before retrieving email headers.'
            );
        }
    }

    /**
     * Retrieve a specific Email Header
     *
     * @param string $name Header name (case-insensitive)
     */
    public function getHeader(string $name): string|false
    {
        $rawHeader = $this->getRawHeader($name);
        if ($rawHeader === false) {
            return false;
        }

        return $this->decodeHeader($rawHeader);
    }

    /**
     * Retrieve all mail headers
     *
     * @throws Exception
     */
    public function getHeaders(): array
    {
        if (isset($this->parts[1])) {
            $headers = $this->getPart('headers', $this->parts[1]);
            foreach ($headers as &$value) {
                if (is_array($value)) {
                    foreach ($value as &$v) {
                        $v = $this->decodeSingleHeader($v);
                    }
                } else {
                    $value = $this->decodeSingleHeader($value);
                }
            }

            return $headers;
        } else {
            throw new Exception(
                'setPath() or setText() or setStream() must be called before retrieving email headers.'
            );
        }
    }

    /**
     * Retrieve the raw mail headers as a string
     *
     * @throws Exception
     */
    public function getHeadersRaw(): string
    {
        if (isset($this->parts[1])) {
            return $this->getPartHeader($this->parts[1]);
        } else {
            throw new Exception(
                'setPath() or setText() or setStream() must be called before retrieving email headers.'
            );
        }
    }

    /**
     * Retrieve the raw Header of a MIME part
     *
     * @throws Exception
     */
    protected function getPartHeader(array &$part): string
    {
        $header = '';
        if ($this->stream) {
            $header = $this->getPartHeaderFromFile($part);
        } elseif ($this->data) {
            $header = $this->getPartHeaderFromText($part);
        }
        return $header;
    }

    /**
     * Retrieve the Header from a MIME part from file
     */
    protected function getPartHeaderFromFile(array &$part): string
    {
        $start = $part['starting-pos'];
        $end = $part['starting-pos-body'];
        fseek($this->stream, $start, SEEK_SET);
        $header = fread($this->stream, $end - $start);
        return $header;
    }

    /**
     * Retrieve the Header from a MIME part from text
     */
    protected function getPartHeaderFromText(array &$part): string
    {
        $start = $part['starting-pos'];
        $end = $part['starting-pos-body'];
        $header = substr($this->data, $start, $end - $start);
        return $header;
    }

    /**
     * Checks whether a given part ID is a child of another part
     * eg. an RFC822 attachment may have one or more text parts
     *
     * @param string $partId
     * @param string $parentPartId
     */
    protected function partIdIsChildOfPart(string $partId, string $parentPartId): bool
    {
        $parentPartId = $parentPartId.'.';
        return substr($partId, 0, strlen($parentPartId)) == $parentPartId;
    }

    /**
     * Whether the given part ID is a child of any attachment part in the message.
     *
     * @param string $checkPartId
     */
    protected function partIdIsChildOfAnAttachment(string $checkPartId): bool
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
     * @throws Exception
     */
    public function getMessageBody(string $type = 'text'): string
    {
        $mime_types = [
            'text'         => 'text/plain',
            'html'         => 'text/html',
            'htmlEmbedded' => 'text/html',
        ];

        if (in_array($type, array_keys($mime_types))) {
            $part_type = $type === 'htmlEmbedded' ? 'html' : $type;
            $inline_parts = $this->getInlineParts($part_type);
            $body = empty($inline_parts) ? '' : $inline_parts[0];
        } else {
            throw new Exception(
                'Invalid type specified for getMessageBody(). Expected: text, html or htmlEmbedded.'
            );
        }

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
     */
    protected function getEmbeddedData(string $contentId): string
    {
        foreach ($this->parts as $part) {
            if ($this->getPart('content-id', $part) == $contentId) {
                $embeddedData = 'data:';
                $embeddedData .= $this->getPart('content-type', $part);
                $embeddedData .= ';'.$this->getPart('transfer-encoding', $part);
                $embeddedData .= ','.$this->getPartBody($part);
                return $embeddedData;
            }
        }
        return '';
    }

    /**
     * Return an array with the following keys display, address, is_group
     *
     * @param string $name Header name (case-insensitive)
     *
     * @return array<int, array{'display': string, 'address': string, 'is_group': bool}>
     */
    public function getAddresses($name)
    {
        $value = $this->getRawHeader($name);
        $value = (is_array($value)) ? $value[0] : $value;
        $addresses = mailparse_rfc822_parse_addresses($value);
        foreach ($addresses as $i => $item) {
            $addresses[$i]['display'] = $this->decodeHeader($item['display']);
        }
        return $addresses;
    }

    /**
     * Returns the inline parts contents (text or HTML)
     *
     * @return string[] The decoded inline parts.
     */
    public function getInlineParts(string $type = 'text'): array
    {
        $inline_parts = [];
        $mime_types = [
            'text'         => 'text/plain',
            'html'         => 'text/html',
        ];

        if (!in_array($type, array_keys($mime_types))) {
            throw new Exception('Invalid type specified for getInlineParts(). "type" can either be text or html.');
        }

        foreach ($this->parts as $partId => $part) {
            if ($this->getPart('content-type', $part) == $mime_types[$type]
                && $this->getPart('content-disposition', $part) != 'attachment'
                && !$this->partIdIsChildOfAnAttachment($partId)
            ) {
                $headers = $this->getPart('headers', $part);
                $encodingType = array_key_exists('content-transfer-encoding', $headers) ?
                    $headers['content-transfer-encoding'] : '';
                $undecoded_body = $this->decodeContentTransfer($this->getPartBody($part), $encodingType);
                $inline_parts[] = $this->charset->decodeCharset($undecoded_body, $this->getPartCharset($part));
            }
        }

        return $inline_parts;
    }

    /**
     * Returns the attachments contents in order of appearance
     *
     * @return Attachment[]
     */
    public function getAttachments(bool $include_inline = true): array
    {
        $attachments = [];
        $dispositions = $include_inline ? ['attachment', 'inline'] : ['attachment'];
        $non_attachment_types = ['text/plain', 'text/html'];
        $nonameIter = 0;

        foreach ($this->parts as $part) {
            $disposition = $this->getPart('content-disposition', $part);
            $filename = 'noname';

            if (isset($part['disposition-filename'])) {
                $filename = $this->decodeHeader($part['disposition-filename']);
            } elseif (isset($part['content-name'])) {
                // if we have no disposition but we have a content-name, it's a valid attachment.
                // we simulate the presence of an attachment disposition with a disposition filename
                $filename = $this->decodeHeader($part['content-name']);
                $disposition = 'attachment';
            } elseif (in_array($part['content-type'], $non_attachment_types, true)
                && $disposition !== 'attachment') {
                // it is a message body, no attachment
                continue;
            } elseif (substr($part['content-type'], 0, 10) !== 'multipart/'
                && $part['content-type'] !== 'text/plain; (error)' && $disposition != 'inline') {
                // if we cannot get it by getMessageBody(), we assume it is an attachment
                $disposition = 'attachment';
            }
            if (in_array($disposition, ['attachment', 'inline']) === false && !empty($disposition)) {
                $disposition = 'attachment';
            }

            if (in_array($disposition, $dispositions) === true) {
                if ($filename == 'noname') {
                    $nonameIter++;
                    $filename = 'noname'.$nonameIter;
                } else {
                    // Escape all potentially unsafe characters from the filename
                    $filename = preg_replace('((^\.)|\/|[\n|\r|\n\r]|(\.$))', '_', $filename);
                }

                $headersAttachments = $this->getPart('headers', $part);
                $contentidAttachments = $this->getPart('content-id', $part);

                $attachmentStream = $this->getAttachmentStream($part);
                $mimePartStr = $this->getPartComplete($part);

                $attachments[] = new Attachment(
                    $filename,
                    $this->getPart('content-type', $part),
                    $attachmentStream,
                    $disposition,
                    $contentidAttachments,
                    $headersAttachments,
                    $mimePartStr
                );
            }
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
     * @throws Exception
     *
     * @return array Saved attachments paths
     */
    public function saveAttachments(
        string $attach_dir,
        bool $include_inline = true,
        string $filenameStrategy = self::ATTACHMENT_DUPLICATE_SUFFIX
    ): array {
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
     * @throws Exception
     *
     * @return mixed Mime Body Part
     */
    protected function getAttachmentStream(array &$part): mixed
    {
        /** @var resource $temp_fp */
        $temp_fp = tmpfile();

        $headers = $this->getPart('headers', $part);
        $encodingType = array_key_exists('content-transfer-encoding', $headers) ?
            $headers['content-transfer-encoding'] : '';

        if ($temp_fp) {
            if ($this->stream) {
                $start = $part['starting-pos-body'];
                $end = $part['ending-pos-body'];
                fseek($this->stream, $start, SEEK_SET);
                $len = $end - $start;
                $written = 0;
                while ($written < $len) {
                    $write = $len;
                    $data = fread($this->stream, $write);
                    fwrite($temp_fp, $this->decodeContentTransfer($data, $encodingType));
                    $written += $write;
                }
            } elseif ($this->data) {
                $attachment = $this->decodeContentTransfer($this->getPartBodyFromText($part), $encodingType);
                fwrite($temp_fp, $attachment, strlen($attachment));
            }
            fseek($temp_fp, 0, SEEK_SET);
        } else {
            throw new Exception(
                'Could not create temporary files for attachments. Your tmp directory may be unwritable by PHP.'
            );
        }

        return $temp_fp;
    }

    /**
     * Decode the string from Content-Transfer-Encoding
     *
     * @param string $encodedString The string in its original encoded state
     * @param string $encodingType  The encoding type from the Content-Transfer-Encoding header of the part.
     */
    protected function decodeContentTransfer(string $encodedString, string|array $encodingType): string
    {
        if (is_array($encodingType)) {
            $encodingType = $encodingType[0];
        }

        $encodingType = strtolower($encodingType);
        if ($encodingType == 'base64') {
            return base64_decode($encodedString);
        } elseif ($encodingType == 'quoted-printable') {
            return quoted_printable_decode($encodedString);
        } else {
            return $encodedString;
        }
    }

    /**
     * $input can be a string or array
     *
     * @param string|array $input
     */
    protected function decodeHeader(string|array $input): string
    {
        //Sometimes we have 2 label From so we take only the first
        if (is_array($input)) {
            return $this->decodeSingleHeader($input[0]);
        }

        return $this->decodeSingleHeader($input);
    }

    /**
     * Decodes a single header (= string)
     *
     * @param string $input
     */
    protected function decodeSingleHeader(string $input): string
    {
        // For each encoded-word...
        while (preg_match('/(=\?([^?]+)\?(q|b)\?([^?]*)\?=)((\s+)=\?)?/i', $input, $matches)) {
            $encoded = $matches[1];
            $charset = $matches[2];
            $encoding = $matches[3];
            $text = $matches[4];
            $space = isset($matches[6]) ? $matches[6] : '';

            switch (strtolower($encoding)) {
                case 'b':
                    $text = $this->decodeContentTransfer($text, 'base64');
                    break;

                case 'q':
                    $text = str_replace('_', ' ', $text);
                    preg_match_all('/=([a-f0-9]{2})/i', $text, $matches);
                    foreach ($matches[1] as $value) {
                        $text = str_replace('='.$value, chr(hexdec($value)), $text);
                    }
                    break;
            }

            $text = $this->charset->decodeCharset($text, $this->charset->getCharsetAlias($charset));
            $input = str_replace($encoded.$space, $text, $input);
        }

        return $input;
    }

    /**
     * Return the charset of the MIME part
     *
     * @param array $part
     */
    protected function getPartCharset(array $part): string
    {
        if (isset($part['charset'])) {
            return $this->charset->getCharsetAlias($part['charset']);
        } else {
            return 'us-ascii';
        }
    }

    /**
     * Retrieve a specified MIME part
     *
     * @param string $type
     * @param array  $parts
     *
     * @return string|array
     */
    protected function getPart($type, $parts)
    {
        return (isset($parts[$type])) ? $parts[$type] : false;
    }

    /**
     * Retrieve the Body of a MIME part
     *
     * @param array $part
     */
    protected function getPartBody(array &$part): string
    {
        $body = '';
        if ($this->stream) {
            $body = $this->getPartBodyFromFile($part);
        } elseif ($this->data) {
            $body = $this->getPartBodyFromText($part);
        }

        return $body;
    }

    /**
     * Retrieve the Body from a MIME part from file
     *
     * @param array $part
     */
    protected function getPartBodyFromFile(array &$part): string
    {
        $start = $part['starting-pos-body'];
        $end = $part['ending-pos-body'];
        $body = '';
        if ($end - $start > 0) {
            fseek($this->stream, $start, SEEK_SET);
            $body = fread($this->stream, $end - $start);
        }

        return $body;
    }

    /**
     * Retrieve the Body from a MIME part from text
     *
     * @param array $part
     */
    protected function getPartBodyFromText(array &$part): string
    {
        $start = $part['starting-pos-body'];
        $end = $part['ending-pos-body'];

        return substr($this->data, $start, $end - $start);
    }

    /**
     * Retrieve the content of a MIME part
     *
     * @param array $part
     */
    protected function getPartComplete(array &$part): string
    {
        $body = '';
        if ($this->stream) {
            $body = $this->getPartFromFile($part);
        } elseif ($this->data) {
            $body = $this->getPartFromText($part);
        }

        return $body;
    }

    /**
     * Retrieve the content from a MIME part from file
     *
     * @param array $part
     */
    protected function getPartFromFile(array &$part): string
    {
        $start = $part['starting-pos'];
        $end = $part['ending-pos'];
        $body = '';
        if ($end - $start > 0) {
            fseek($this->stream, $start, SEEK_SET);
            $body = fread($this->stream, $end - $start);
        }

        return $body;
    }

    /**
     * Retrieve the content from a MIME part from text
     *
     * @param array $part
     */
    protected function getPartFromText(array &$part): string
    {
        $start = $part['starting-pos'];
        $end = $part['ending-pos'];

        return substr($this->data, $start, $end - $start);
    }

    /**
     * Retrieve the resource
     */
    public function getResource(): mixed
    {
        return $this->resource;
    }

    /**
     * Retrieve the file pointer to email
     */
    public function getStream(): mixed
    {
        return $this->stream;
    }

    /**
     * Retrieve the text of an email
     */
    public function getData(): string
    {
        return $this->data;
    }

    /**
     * Retrieve the parts of an email
     */
    public function getParts(): array
    {
        return $this->parts;
    }

    /**
     * Retrieve the charset manager object
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
     */
    public function addMiddleware(callable $middleware): void
    {
        if (!$middleware instanceof Middleware) {
            $middleware = new Middleware($middleware);
        }
        $this->middlewareStack = $this->middlewareStack->add($middleware);
    }
}
