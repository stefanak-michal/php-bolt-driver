<?php

namespace Bolt\tests\packstream\v1\generators;

/**
 * Class ListGenerator
 *
 * @author Michal Stefanak
 * @link https://github.com/stefanak-michal/php-bolt-driver
 * @package Bolt\tests\packstream\v1\generators
 */
class ListGenerator implements \Bolt\packstream\IPackListGenerator
{
    public function __construct(
        public array $array,
        private int  $position = 0
    )
    {
    }

    public function rewind(): void
    {
        $this->position = 0;
    }

    public function current(): mixed
    {
        return $this->array[$this->position];
    }

    public function key(): int
    {
        return $this->position;
    }

    public function next(): void
    {
        ++$this->position;
    }

    public function valid(): bool
    {
        return array_key_exists($this->position, $this->array);
    }

    public function count(): int
    {
        return count($this->array);
    }
}
