<?php

namespace PhpMimeMailParser;

/**
 * Mime Part
 * Represents the results of mailparse_msg_get_part_data()
 *
 * Note ArrayAccess::offsetSet() cannot modify deeply nestated arrays.
 * When modifying use getPart() and setPart() for deep nested data modification
 *
 * @example
 *
 *     $MimePart['headers']['from'] = 'modified@example.com' // fails
 *
 *     // correct
 *     $part = $MimePart->getPart();
 *     $part['headers']['from'] = 'modified@example.com';
 *     $MimePart->setPart($part);
 */
final class Entity implements \ArrayAccess
{
    /**
     * @var \PhpMimeMailParser\ParserConfig|mixed
     */
    public $parserConfig;
    /**
     * Internal mime part
     *
     * @var array
     */
    protected $part = [];

    /**
     * Immutable Part Id
     *
     * @var string
     */
    private $id;
    private $stream;
    private $data;

    /**
     * Create a mime part
     *
     * @param array $part
     * @param string $id
     */
    public function __construct(string $entityId, array $part, $stream = null, $data = null, $parserConfig = null)
    {
        $this->part = $part;
        $this->id = $entityId;
        $this->stream = $stream;
        $this->data = $data;
        $this->parserConfig = $parserConfig ?? new ParserConfig;
    }

    /**
     * Retrieve the part Id
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Retrieve the part data
     *
     * @return mixed[]
     */
    public function getPart(): array
    {
        return $this->part;
    }

    /**
     * Set the mime part data
     *
     * @param mixed[] $part
     */
    public function setPart(array $part): void
    {
        $this->part = $part;
    }

    /**
     * ArrayAccess
     */
    public function offsetSet($offset, $value): void
    {
        if (is_null($offset)) {
            $this->part[] = $value;
            return;
        }
        $this->part[$offset] = $value;
    }

    /**
     * ArrayAccess
     */
    public function offsetExists($offset): bool
    {
        return isset($this->part[$offset]);
    }

    /**
     * ArrayAccess
     */
    public function offsetUnset($offset): void
    {
        unset($this->part[$offset]);
    }

    /**
     * ArrayAccess
     */
    public function offsetGet($offset)
    {
        return isset($this->part[$offset]) ? $this->part[$offset] : null;
    }

    /**
     * @return mixed|null
     */
    private function getField($type)
    {
        if (array_key_exists($type, $this->part)) {
            return $this->part[$type];
        }

        return null;
    }

    public function getDispositionFileName()
    {
        return $this->getField('disposition-filename');
    }

    public function getContentName()
    {
        return $this->getField('content-name');
    }

    public function getContentType()
    {
        return $this->getField('content-type');
    }

    public function getContentDisposition()
    {
        return $this->getField('content-disposition');
    }

    public function getContentId()
    {
        return $this->getField('content-id');
    }

    public function getContentTransferEncoding()
    {
        return $this->getField('transfer-encoding');
    }

    public function getHeaders()
    {
        $headers = $this->getHeadersRaw();
        array_walk_recursive($headers, function (&$value): void {
            $value = $this->parserConfig->getMimeHeaderEncodingManager()->decodeHeader($value);
        });
        return $headers;
    }

    public function getHeadersRaw()
    {
        return $this->getField('headers');
    }

    /**
     * @return null|mixed
     */
    public function getHeader($name)
    {
        $raw = $this->getHeaderRaw($name);
        if ($raw == null) {
            return null;
        }
        return $this->parserConfig->getMimeHeaderEncodingManager()->decodeHeader($raw);
    }

    /**
     * @return null|mixed
     */
    public function getHeaderRaw($name)
    {
        $name = strtolower($name);
        $headers = $this->getField('headers');

        if (!array_key_exists($name, $headers)) {
            return null;
        }

        if (\is_array($headers[$name])) {
            return $headers[$name][0];
        }

        return $headers[$name];
    }

    /**
     * @return mixed[]
     */
    public function getAddressesRaw($name): array
    {
        $raw = $this->getHeaderRaw($name);

        return mailparse_rfc822_parse_addresses($raw);
    }

    public function getAddresses($name)
    {
        $addresses = $this->getAddressesRaw($name);

        foreach ($addresses as $i => $item) {
            $addresses[$i]['display'] = $this->parserConfig->getMimeHeaderEncodingManager()->decodeHeader($item['display']);
        }

        return $addresses;
    }

    public function getStartingPositionBody()
    {
        return $this->getField('starting-pos-body');
    }

    public function getEndingPositionBody()
    {
        return $this->getField('ending-pos-body');
    }

    public function getStartingPosition()
    {
        return $this->getField('starting-pos');
    }

    public function getEndingPosition()
    {
        return $this->getField('ending-pos');
    }

    public function getCharset()
    {
        return $this->getField('charset');
    }

    public function getBodyData($start, $end)
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
     * @return string|bool
     */
    public function getCompleteBody()
    {
        return $this->getBodyData(
            $this->getStartingPosition(),
            $this->getEndingPosition()
        );
    }

    /**
     * @return string|bool
     */
    public function getBody()
    {
        return $this->getBodyData(
            $this->getStartingPositionBody(),
            $this->getEndingPositionBody()
        );
    }

    public function isTextMessage($subType): bool
    {
        $disposition = $this->getContentDisposition();
        $contentType = $this->getContentType();

        if (($disposition == 'inline' || empty($disposition)) && $contentType == 'text/'.$subType) {
            return true;
        }
        return false;
    }

    public function decoded()
    {
        $undecodedBody = $this->parserConfig->getContentTransferEncodingManager()->decodeContentTransfer(
            $this->getBody(),
            $this->getContentTransferEncoding()
        );
        return $this->parserConfig->getCharsetManager()->decodeCharset($undecodedBody, $this->getCharset());
    }

    public function parse()
    {
        return $this->parserConfig->middlewareStack->parse($this);
    }
}
