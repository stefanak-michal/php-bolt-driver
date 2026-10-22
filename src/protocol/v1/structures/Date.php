<?php

namespace Bolt\protocol\v1\structures;

use Bolt\protocol\IStructure;

/**
 * Class Date
 * Immutable
 *
 * An instant capturing the date, but not the time, nor the time zone
 *
 * @author Michal Stefanak
 * @link https://github.com/stefanak-michal/php-bolt-driver
 * @link https://www.neo4j.com/docs/bolt/current/bolt/structure-semantics/#structure-date
 * @package Bolt\protocol\v1\structures
 */
class Date implements IStructure
{
    /**
     * @param int $days days since the Unix epoch
     */
    public function __construct(public readonly int $days)
    {
    }

    public function __toString(): string
    {
        return gmdate('Y-m-d', strtotime(sprintf("%+d", $this->days) . ' days +0000', 0));
    }
}
