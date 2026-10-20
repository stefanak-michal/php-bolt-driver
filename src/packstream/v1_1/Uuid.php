<?php

namespace Bolt\packstream\v1_1;

use Bolt\error\PackException;

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
    public readonly string $value;

    /**
     * @throws PackException In case of invalid UUID format
     */
    public function __construct(string $value)
    {
        if (preg_match("/^[a-f\d]{8}-([a-f\d]{4}-){3}[a-f\d]{12}$/i", $value) !== 1) {
            throw new PackException('UUID has invalid format');
        }
        $this->value = $value;
    }
}
