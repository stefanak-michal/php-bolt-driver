<?php

namespace Bolt\protocol\v1\structures;

use Bolt\protocol\IStructure;

/**
 * Class Node
 * Immutable
 *
 * @author Michal Stefanak
 * @link https://github.com/stefanak-michal/php-bolt-driver
 * @link https://www.neo4j.com/docs/bolt/current/bolt/structure-semantics/#structure-node
 * @package Bolt\protocol\v1\structures
 */
class Node implements IStructure
{
    public function __construct(
        public readonly int   $id,
        public readonly array $labels,
        public readonly array $properties
    )
    {
    }

    public function __toString(): string
    {
        return json_encode([
            'identity' => $this->id,
            'labels' => $this->labels,
            'properties' => $this->properties
        ]);
    }
}
