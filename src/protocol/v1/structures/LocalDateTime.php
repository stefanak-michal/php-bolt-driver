<?php

namespace Bolt\protocol\v1\structures;

use Bolt\protocol\IStructure;

/**
 * Class LocalDateTime
 * Immutable
 *
 * An instant capturing the date and the time, but not the time zone
 *
 * @author Michal Stefanak
 * @link https://github.com/stefanak-michal/php-bolt-driver
 * @link https://www.neo4j.com/docs/bolt/current/bolt/structure-semantics/#structure-localdatetime
 * @package Bolt\protocol\v1\structures
 */
class LocalDateTime implements IStructure
{
    /**
     * @param int $seconds seconds since the Unix epoch
     * @param int $nanoseconds
     */
    public function __construct(
        public readonly int $seconds,
        public readonly int $nanoseconds
    )
    {
    }

    public function __toString(): string
    {
        $datetime = sprintf("%d", $this->seconds)
            . '.' . substr(sprintf("%09d", $this->nanoseconds), 0, 6);
        return \DateTime::createFromFormat('U.u', $datetime)
            ->format('Y-m-d\TH:i:s.u');
    }

}
