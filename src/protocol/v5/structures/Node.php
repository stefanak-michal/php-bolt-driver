<?php

namespace Bolt\protocol\v5\structures;

use Bolt\protocol\v1\structures\Node as v1_Node;

/**
 * Class Node
 * Immutable
 *
 * @author Michal Stefanak
 * @link https://github.com/stefanak-michal/php-bolt-driver
 * @link https://www.neo4j.com/docs/bolt/current/bolt/structure-semantics/#structure-node
 * @package Bolt\protocol\v5\structures
 */
class Node extends v1_Node
{
    public function __construct(
        int                    $id,
        array                  $labels,
        array                  $properties,
        public readonly string $element_id
    )
    {
        parent::__construct($id, $labels, $properties);
    }

    public function __toString(): string
    {
        return json_encode([
            'identity' => $this->id,
            'labels' => $this->labels,
            'properties' => $this->properties,
            'element_id' => $this->element_id
        ]);
    }
}
