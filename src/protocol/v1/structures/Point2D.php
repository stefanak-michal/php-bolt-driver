<?php

namespace Bolt\protocol\v1\structures;

use Bolt\protocol\IStructure;

/**
 * Class Point2D
 * Immutable
 *
 * Represents a single location in 2-dimensional space.
 *
 * @author Michal Stefanak
 * @link https://github.com/stefanak-michal/php-bolt-driver
 * @link https://www.neo4j.com/docs/bolt/current/bolt/structure-semantics/#structure-point2d
 * @package Bolt\protocol\v1\structures
 */
class Point2D implements IStructure
{
    /**
     * @param int $srid Spatial Reference System Identifier
     * @param float $x
     * @param float $y
     */
    public function __construct(
        public readonly int   $srid,
        public readonly float $x,
        public readonly float $y
    )
    {
    }

    public function __toString(): string
    {
        return 'point({srid: ' . $this->srid . ', ' . 'x: ' . $this->x . ', y: ' . $this->y . '})';
    }
}
