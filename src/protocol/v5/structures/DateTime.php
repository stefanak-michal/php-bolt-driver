<?php

namespace Bolt\protocol\v5\structures;

use Bolt\protocol\v1\structures\DateTime as v1_DateTime;

/**
 * Class DateTime
 * Immutable
 *
 * An instant capturing the date, the time, and the time zone.
 * The time zone information is specified with a zone offset.
 *
 * @author Michal Stefanak
 * @link https://github.com/stefanak-michal/php-bolt-driver
 * @link https://www.neo4j.com/docs/bolt/current/bolt/structure-semantics/#structure-datetime
 * @package Bolt\protocol\v5\structures
 */
class DateTime extends v1_DateTime
{
    public function __toString(): string
    {
        $datetime = sprintf("%d", $this->seconds) . '.' . substr(sprintf("%09d", $this->nanoseconds), 0, 6);
        return \DateTime::createFromFormat('U.u', $datetime, new \DateTimeZone('UTC'))
            ->setTimezone(new \DateTimeZone(sprintf("%+'05d", $this->tz_offset_seconds / 3600 * 100)))
            ->format('Y-m-d\TH:i:s.uP');
    }
}
