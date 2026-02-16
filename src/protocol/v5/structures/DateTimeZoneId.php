<?php

namespace Bolt\protocol\v5\structures;

use Bolt\protocol\v1\structures\DateTimeZoneId as v1_DateTimeZoneId;

/**
 * Class DateTimeZoneId
 * Immutable
 *
 * An instant capturing the date, the time, and the time zone.
 * The time zone information is specified with a zone identification number.
 *
 * @author Michal Stefanak
 * @link https://github.com/stefanak-michal/php-bolt-driver
 * @link https://www.neo4j.com/docs/bolt/current/bolt/structure-semantics/#structure-legacy-datetimezoneid
 * @package Bolt\protocol\v5\structures
 */
class DateTimeZoneId extends v1_DateTimeZoneId
{
    public function __toString(): string
    {
        $datetime = sprintf("%d", $this->seconds) . '.' . substr(sprintf("%09d", $this->nanoseconds), 0, 6);
        return \DateTime::createFromFormat('U.u', $datetime, new \DateTimeZone('UTC'))
            ->setTimezone(new \DateTimeZone($this->tz_id))
            ->format('Y-m-d\TH:i:s.u') . '[' . $this->tz_id . ']';
    }
}
