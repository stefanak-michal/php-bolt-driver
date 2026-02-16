<?php

namespace Bolt\protocol\v5\structures;

use Bolt\protocol\v1\structures\UnboundRelationship as v1_UnboundRelationship;

/**
 * Class UnboundRelationship
 * Immutable
 *
 * @author Michal Stefanak
 * @link https://github.com/stefanak-michal/php-bolt-driver
 * @link https://www.neo4j.com/docs/bolt/current/bolt/structure-semantics/#structure-unbound
 * @package Bolt\protocol\v5\structures
 */
class UnboundRelationship extends v1_UnboundRelationship
{
    public function __construct(
        int                    $id,
        string                 $type,
        array                  $properties,
        public readonly string $element_id
    )
    {
        parent::__construct($id, $type, $properties);
    }

    public function __toString(): string
    {
        return json_encode([
            'identity' => $this->id,
            'type' => $this->type,
            'properties' => $this->properties,
            'element_id' => $this->element_id
        ]);
    }
}
