<?php

namespace Bolt\protocol\v1\structures;

use Bolt\protocol\IStructure;

/**
 * Class DateTime
 * Immutable
 *
 * An instant capturing the date, the time, and the time zone.
 * The time zone information is specified with a zone offset.
 *
 * To convert to UTC:
 * <pre> utc_nanoseconds = (seconds * 1000000000) + nanoseconds - (tx_offset_minutes * 60 * 1000000000) </pre>
 *
 * @author Michal Stefanak
 * @link https://github.com/stefanak-michal/php-bolt-driver
 * @link https://www.neo4j.com/docs/bolt/current/bolt/structure-semantics/#structure-legacy-datetime
 * @package Bolt\protocol\v1\structures
 */
class DateTime implements IStructure
{
    /**
     * @param int $seconds seconds since the adjusted Unix epoch. This is not UTC
     * @param int $nanoseconds
     * @param int $tz_offset_seconds specifies the offset in seconds from UTC
     */
    public function __construct(
        public readonly int $seconds,
        public readonly int $nanoseconds,
        public readonly int $tz_offset_seconds
    )
    {
    }

    public function __toString(): string
    {
        $datetime = sprintf("%d", $this->seconds - $this->tz_offset_seconds)
            . '.' . substr(sprintf("%09d", $this->nanoseconds), 0, 6);
        return \DateTime::createFromFormat('U.u', $datetime, new \DateTimeZone('UTC'))
            ->setTimezone(new \DateTimeZone(sprintf("%+'05d", $this->tz_offset_seconds / 3600 * 100)))
            ->format('Y-m-d\TH:i:s.uP');
    }
}
