<?php

namespace Bolt\protocol\v4_3;

use Bolt\protocol\v1\structures\{
    Date,
    DateTime,
    DateTimeZoneId,
    Duration,
    LocalDateTime,
    LocalTime,
    Node,
    Path,
    Point2D,
    Point3D,
    Relationship,
    Time,
    UnboundRelationship
};
use Bolt\protocol\v5\structures\{
    DateTime as v5_DateTime,
    DateTimeZoneId as v5_DateTimeZoneId
};

/**
 * Trait to set available structures
 *
 * @author Michal Stefanak
 * @link https://github.com/stefanak-michal/php-bolt-driver
 * @see https://www.neo4j.com/docs/bolt/current/bolt/structure-semantics/
 * @package Bolt\protocol\v4_3
 */
trait AvailableStructures
{
    protected array $packStructuresLt = [
        0x44 => Date::class,
        0x54 => Time::class,
        0x74 => LocalTime::class,
        0x46 => DateTime::class,
        0x49 => v5_DateTime::class,
        0x66 => DateTimeZoneId::class,
        0x69 => v5_DateTimeZoneId::class,
        0x64 => LocalDateTime::class,
        0x45 => Duration::class,
        0x58 => Point2D::class,
        0x59 => Point3D::class,
    ];

    protected array $unpackStructuresLt = [
        0x4E => Node::class,
        0x52 => Relationship::class,
        0x72 => UnboundRelationship::class,
        0x50 => Path::class,
        0x44 => Date::class,
        0x54 => Time::class,
        0x74 => LocalTime::class,
        0x46 => DateTime::class,
        0x49 => v5_DateTime::class,
        0x66 => DateTimeZoneId::class,
        0x69 => v5_DateTimeZoneId::class,
        0x64 => LocalDateTime::class,
        0x45 => Duration::class,
        0x58 => Point2D::class,
        0x59 => Point3D::class,
    ];
}
