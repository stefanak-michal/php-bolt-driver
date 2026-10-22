<?php

namespace Bolt\protocol\v1\structures;

use Bolt\protocol\IStructure;

/**
 * Class LocalTime
 * Immutable
 *
 * An instant capturing the time of day, but not the date, nor the time zone
 *
 * @author Michal Stefanak
 * @link https://github.com/stefanak-michal/php-bolt-driver
 * @link https://www.neo4j.com/docs/bolt/current/bolt/structure-semantics/#structure-localtime
 * @package Bolt\protocol\v1\structures
 */
class LocalTime implements IStructure
{
    /**
     * @param int $nanoseconds nanosecond since midnight
     */
    public function __construct(public readonly int $nanoseconds)
    {
    }

    public function __toString(): string
    {
        $value = sprintf("%09d", $this->nanoseconds);
        $seconds = substr($value, 0, -9);
        if (empty($seconds))
            $seconds = '0';
        $fraction = substr($value, -9, 6);

        return \DateTime::createFromFormat('U.u', $seconds . '.' . $fraction)
            ->format('H:i:s.u');
    }
}
