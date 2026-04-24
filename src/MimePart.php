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
class MimePart implements \ArrayAccess
{
    /**
     * Internal mime part
     */
    protected array $part = [];

    /**
     * Immutable Part Id
     */
    private string $id;

    /**
     * Create a mime part
     */
    public function __construct(string $id, array $part)
    {
        $this->part = $part;
        $this->id = $id;
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
     */
    public function getPart(): array
    {
        return $this->part;
    }

    /**
     * Set the mime part data
     */
    public function setPart(array $part): void
    {
        $this->part = $part;
    }

    /**
     * ArrayAccess
     */
    #[\ReturnTypeWillChange]
    public function offsetSet($offset, $value)
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
    #[\ReturnTypeWillChange]
    public function offsetExists($offset)
    {
        return isset($this->part[$offset]);
    }

    /**
     * ArrayAccess
     */
    #[\ReturnTypeWillChange]
    public function offsetUnset($offset)
    {
        unset($this->part[$offset]);
    }

    /**
     * ArrayAccess
     */
    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        return isset($this->part[$offset]) ? $this->part[$offset] : null;
    }
}
