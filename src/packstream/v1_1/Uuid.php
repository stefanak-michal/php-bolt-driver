<?php

namespace Bolt\packstream\v1_1;

/**
 * Class Uuid
 *
 * @author Michal Stefanak
 * @link https://github.com/stefanak-michal/php-bolt-driver
 * @link https://neo4j.com/docs/bolt/current/packstream/#data-type-uuid
 * @package Bolt\packstream\v1_1
 */
class Uuid
{
    public function __construct(public readonly string $value)
    {
    }
}
