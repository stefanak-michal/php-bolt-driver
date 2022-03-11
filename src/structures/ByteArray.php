<?php

namespace Bolt\structures;

use ArrayAccess, Countable;

/**
 * Class ByteArray
 *
 * @author Michal Stefanak
 * @link https://github.com/neo4j-php/Bolt
 * @link https://7687.org/packstream/packstream-specification-1.html#bytes
 * @package Bolt\structures
 */
class ByteArray implements ArrayAccess, Countable
{
    private array $bytes = [];

    public function __construct(array $bytes = [])
    {
        $this->bytes = $bytes;
    }

    public function offsetExists($offset): bool
    {
        return array_key_exists($offset, $this->bytes);
    }

    public function offsetGet($offset): ?string
    {
        return $this->bytes[$offset] ?? null;
    }

    public function offsetSet($offset, $value)
    {
        if ($offset === null)
            $this->bytes[] = $value;
        else
            $this->bytes[$offset] = $value;
    }

    public function offsetUnset($offset)
    {
        unset($this->bytes[$offset]);
    }

    public function count(): int
    {
        return count($this->bytes);
    }

    public function __toString(): string
    {
        return implode($this->bytes);
    }
}
