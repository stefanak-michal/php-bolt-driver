<?php

namespace Bolt\protocol\v1\structures;

use Bolt\protocol\IStructure;

/**
 * Class Relationship
 * Immutable
 *
 * @author Michal Stefanak
 * @link https://github.com/stefanak-michal/php-bolt-driver
 * @link https://www.neo4j.com/docs/bolt/current/bolt/structure-semantics/#structure-relationship
 * @package Bolt\protocol\v1\structures
 */
class Relationship implements IStructure
{
    public function __construct(
        public readonly int    $id,
        public readonly int    $startNodeId,
        public readonly int    $endNodeId,
        public readonly string $type,
        public readonly array  $properties
    )
    {
    }

    public function __toString(): string
    {
        return json_encode([
            'identity' => $this->id,
            'start' => $this->startNodeId,
            'end' => $this->endNodeId,
            'type' => $this->type,
            'properties' => $this->properties
        ]);
    }
}
