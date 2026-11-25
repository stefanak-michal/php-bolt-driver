<?php

namespace Bolt\packstream\v1_1;

use InvalidArgumentException;

/**
 * @author Michal Stefanak
 * @link https://github.com/stefanak-michal/php-bolt-driver
 * @link https://neo4j.com/docs/bolt/current/packstream/#data-type-uuid
 * @package Bolt\packstream\v1_1
 */
readonly class Uuid
{
    public string $value;

    /**
     * @throws InvalidArgumentException In case of invalid UUID format
     */
    public function __construct(string $value)
    {
        if (preg_match("/^[a-f\d]{8}-([a-f\d]{4}-){3}[a-f\d]{12}$/i", $value) !== 1) {
            throw new InvalidArgumentException('UUID has invalid format');
        }
        $this->value = $value;
    }
}
