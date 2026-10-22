<?php

namespace Bolt\protocol\v1\structures;

use Bolt\protocol\IStructure;

/**
 * Class Path
 * Immutable
 *
 * @author Michal Stefanak
 * @link https://github.com/stefanak-michal/php-bolt-driver
 * @link https://www.neo4j.com/docs/bolt/current/bolt/structure-semantics/#structure-path
 * @package Bolt\protocol\v1\structures
 */
class Path implements IStructure
{
    /**
     * List of integers describing how to construct the path from nodes and rels
     * @var int[]
     */
    public readonly array $indices;

    /**
     * @deprecated use $indices instead. For some reason originally I named it ids, but the correct name is indices.
     * @var int[]
     */
    public readonly array $ids;

    /**
     * @param Node[] $nodes List of nodes
     * @param UnboundRelationship[] $rels List of unbound relationships
     * @param int[] $indices Relationship id and node id to represent the path
     */
    public function __construct(
        public readonly array $nodes,
        public readonly array $rels,
        array $indices
    )
    {
        $this->ids = $indices;
        $this->indices = $indices;
    }

    public function __toString(): string
    {
        $obj = [
            'start' => json_decode(reset($this->nodes), true),
            'end' => json_decode(end($this->nodes), true),
            'segments' => [],
            'length' => count($this->indices) - 1
        ];

        for ($i = 0; $i < count($this->nodes) - 1; $i++) {
            $obj['segments'][] = [
                'start' => json_decode($this->nodes[$i], true),
                'relationship' => array_merge(json_decode($this->rels[$i], true), ['start' => $this->nodes[$i]->id, 'end' => $this->nodes[$i + 1]->id()]),
                'end' => json_decode($this->nodes[$i + 1], true)
            ];
        }

        return json_encode($obj);
    }
}
