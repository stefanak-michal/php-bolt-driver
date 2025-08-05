<?php

namespace Bolt\protocol\v6;

use Bolt\protocol\v1\structures\{
    Date,
    Duration,
    LocalDateTime,
    LocalTime,
    Path,
    Point2D,
    Point3D,
    Time,
};
use Bolt\protocol\v5\structures\{
    DateTime,
    DateTimeZoneId,
    Node,
    Relationship,
    UnboundRelationship
};
use Bolt\protocol\v6\structures\Vector;

/**
 * Trait to set available structures
 *
 * @author Michal Stefanak
 * @link https://github.com/neo4j-php/Bolt
 * @see https://www.neo4j.com/docs/bolt/current/bolt/structure-semantics/
 * @package Bolt\protocol
 */
trait AvailableStructures
{
    protected array $packStructuresLt = [
        0x44 => Date::class,
        0x54 => Time::class,
        0x74 => LocalTime::class,
        0x49 => DateTime::class,
        0x69 => DateTimeZoneId::class,
        0x64 => LocalDateTime::class,
        0x45 => Duration::class,
        0x58 => Point2D::class,
        0x59 => Point3D::class,
        0x56 => Vector::class,
    ];

    protected array $unpackStructuresLt = [
        0x4E => Node::class,
        0x52 => Relationship::class,
        0x72 => UnboundRelationship::class,
        0x50 => Path::class,
        0x44 => Date::class,
        0x54 => Time::class,
        0x74 => LocalTime::class,
        0x49 => DateTime::class,
        0x69 => DateTimeZoneId::class,
        0x64 => LocalDateTime::class,
        0x45 => Duration::class,
        0x58 => Point2D::class,
        0x59 => Point3D::class,
        0x56 => Vector::class,
    ];
}
