<?php

namespace Bolt\protocol\v5\structures;

use Bolt\protocol\v1\structures\Relationship as v1_Relationship;

/**
 * Class Relationship
 * Immutable
 *
 * @author Michal Stefanak
 * @link https://github.com/stefanak-michal/php-bolt-driver
 * @link https://www.neo4j.com/docs/bolt/current/bolt/structure-semantics/#structure-relationship
 * @package Bolt\protocol\v5\structures
 */
class Relationship extends v1_Relationship
{
    public function __construct(
        int                    $id,
        int                    $startNodeId,
        int                    $endNodeId,
        string                 $type,
        array                  $properties,
        public readonly string $element_id,
        public readonly string $start_node_element_id,
        public readonly string $end_node_element_id
    )
    {
        parent::__construct($id, $startNodeId, $endNodeId, $type, $properties);
    }

    public function __toString(): string
    {
        return json_encode([
            'identity' => $this->id,
            'start' => $this->startNodeId,
            'end' => $this->endNodeId,
            'type' => $this->type,
            'properties' => $this->properties,
            'element_id' => $this->element_id,
            'start_node_element_id' => $this->start_node_element_id,
            'end_node_element_id' => $this->end_node_element_id
        ]);
    }
}
