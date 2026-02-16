<?php

namespace Bolt\protocol\v1\structures;

use Bolt\protocol\IStructure;

/**
 * Class Duration
 * Immutable
 *
 * A temporal amount. This captures the difference in time between two instants. It only captures the amount of time between two instants, it does not capture a start time and end time. A unit capturing the start time and end time would be a Time Interval and is out of scope for this proposal.
 *
 * @author Michal Stefanak
 * @link https://github.com/stefanak-michal/php-bolt-driver
 * @link https://www.neo4j.com/docs/bolt/current/bolt/structure-semantics/#structure-duration
 * @package Bolt\protocol\v1\structures
 */
class Duration implements IStructure
{
    public function __construct(
        public readonly int $months,
        public readonly int $days,
        public readonly int $seconds,
        public readonly int $nanoseconds
    )
    {
    }

    public function __toString(): string
    {
        $output = 'P';
        $years = floor($this->months / 12);
        if (!empty($years))
            $output .= $years . 'Y';
        if (!empty($this->months % 12))
            $output .= ($this->months % 12) . 'M';
        if (!empty($this->days))
            $output .= $this->days . 'D';

        $time = '';
        $hours = floor($this->seconds / 3600);
        if (!empty($hours))
            $time .= $hours . 'H';
        $minutes = floor($this->seconds % 3600 / 60);
        if (!empty($minutes))
            $time .= $minutes . 'M';

        $seconds = rtrim(sprintf("%d", $this->seconds % 3600 % 60)
            . '.' . substr(sprintf("%09d", $this->nanoseconds), 0, 6), '0.');
        if (!empty($seconds))
            $time .= $seconds . 'S';

        if (!empty($time))
            $output .= 'T' . $time;

        return $output;
    }
}
