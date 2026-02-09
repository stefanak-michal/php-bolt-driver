<?php

namespace Bolt\tests\structures;

use Bolt\tests\TestLayer;
use Exception;

/**
 * Class AStructures
 * @author Michal Stefanak
 * @link https://github.com/neo4j-php/Bolt
 * @package Bolt\tests\protocol
 */
abstract class StructureLayer extends TestLayer
{
    /**
     * How many iterations do for each date/time test
     */
    public static int $iterations = 50;

    /**
     * @var array<int> List of already generated timestamps to avoid duplicates
     */
    private array $generatedTimestamps = [];

    public function providerTimestamp(): \Generator
    {
        for ($i = 0; $i < self::$iterations; $i++) {
            do {
                $ts = $this->randomTimestamp();
            } while (in_array($ts, $this->generatedTimestamps, true));
            $this->generatedTimestamps[] = $ts;
            yield 'ts: ' . $ts => [$ts];
        }
    }

    public function providerTimestampTimezone(): \Generator
    {
        for ($i = 0; $i < self::$iterations; $i++) {
            $tz = \DateTimeZone::listIdentifiers()[array_rand(\DateTimeZone::listIdentifiers())];
            do {
                $ts = $this->randomTimestamp($tz);
            } while (in_array($ts, $this->generatedTimestamps, true));
            $this->generatedTimestamps[] = $ts;
            yield 'ts: ' . $ts . ' tz: ' . $tz => [$ts, $tz];
        }
    }

    private function randomTimestamp(string $timezone = '+0000'): int
    {
        try {
            $zone = new \DateTimeZone($timezone);
            $start = new \DateTime(date('Y-m-d H:i:s', strtotime('-10 years', 0)), $zone);
            $end = new \DateTime(date('Y-m-d H:i:s', strtotime('+10 years', 0)), $zone);
            return rand($start->getTimestamp(), $end->getTimestamp());
        } catch (Exception) {
            return strtotime('now ' . $timezone);
        }
    }
}
