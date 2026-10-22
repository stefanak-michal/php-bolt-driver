<?php

namespace Bolt\protocol;

/**
 * Class Protocol version 6.1
 *
 * @author Michal Stefanak
 * @link https://github.com/stefanak-michal/php-bolt-driver
 * @see https://www.neo4j.com/docs/bolt/current/bolt/message/
 * @package Bolt\protocol
 */
class V6_1 extends V6
{
    protected function getPackStreamVersion(): string
    {
        return '1_1';
    }
}
