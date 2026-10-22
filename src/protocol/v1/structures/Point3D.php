<?php

namespace Bolt\protocol\v1\structures;

use Bolt\protocol\IStructure;

/**
 * Class Point3D
 * Immutable
 *
 * Represents a single location in space.
 *
 * @author Michal Stefanak
 * @link https://github.com/stefanak-michal/php-bolt-driver
 * @link https://www.neo4j.com/docs/bolt/current/bolt/structure-semantics/#structure-point3d
 * @package Bolt\protocol\v1\structures
 */
class Point3D implements IStructure
{
    /**
     * @param int $srid Spatial Reference System Identifier
     * @param float $x
     * @param float $y
     * @param float $z
     */
    public function __construct(
        public readonly int   $srid,
        public readonly float $x,
        public readonly float $y,
        public readonly float $z
    )
    {
    }

    public function __toString(): string
    {
        return 'point({srid: ' . $this->srid . ', ' . 'x: ' . $this->x . ', y: ' . $this->y . ', z: ' . $this->z . '})';
    }

}
