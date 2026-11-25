<?php

namespace Bolt\protocol\v1\structures;

use Bolt\protocol\IStructure;

/**
 * @author Michal Stefanak
 * @link https://github.com/stefanak-michal/php-bolt-driver
 * @link https://www.neo4j.com/docs/bolt/current/bolt/structure-semantics/#structure-relationship
 * @package Bolt\protocol\v1\structures
 */
readonly class Relationship implements IStructure
{
    public function __construct(
        public int $id,
        public int $startNodeId,
        public int $endNodeId,
        public string $type,
        public array $properties,
    ) {}

    public function __toString(): string
    {
        return json_encode([
            'identity' => $this->id,
            'start' => $this->startNodeId,
            'end' => $this->endNodeId,
            'type' => $this->type,
            'properties' => $this->properties,
        ]);
    }
}
