<?php

namespace Bolt\protocol\v1\structures;

use Bolt\protocol\IStructure;

/**
 * Class UnboundRelationship
 * Immutable
 *
 * @author Michal Stefanak
 * @link https://github.com/stefanak-michal/php-bolt-driver
 * @link https://www.neo4j.com/docs/bolt/current/bolt/structure-semantics/#structure-unbound
 * @package Bolt\protocol\v1\structures
 */
class UnboundRelationship implements IStructure
{
    public function __construct(
        public readonly int    $id,
        public readonly string $type,
        public readonly array  $properties
    )
    {
    }

    public function __toString(): string
    {
        return json_encode([
            'identity' => $this->id,
            'type' => $this->type,
            'properties' => $this->properties
        ]);
    }
}
