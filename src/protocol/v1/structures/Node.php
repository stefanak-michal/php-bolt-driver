<?php

namespace Bolt\protocol\v1\structures;

use Bolt\protocol\IStructure;

/**
 * @author Michal Stefanak
 * @link https://github.com/stefanak-michal/php-bolt-driver
 * @link https://www.neo4j.com/docs/bolt/current/bolt/structure-semantics/#structure-node
 * @package Bolt\protocol\v1\structures
 */
readonly class Node implements IStructure
{
    public function __construct(
        public int $id,
        public array $labels,
        public array $properties,
    ) {}

    public function __toString(): string
    {
        return json_encode([
            'identity' => $this->id,
            'labels' => $this->labels,
            'properties' => $this->properties,
        ]);
    }
}
