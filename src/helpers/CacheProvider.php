<?php

namespace Bolt\helpers;

use Psr\SimpleCache\CacheInterface;

/**
 * Class CacheProvider
 *
 * @author Michal Stefanak
 * @link https://github.com/stefanak-michal/php-bolt-driver
 * @package Bolt\helpers
 */
class CacheProvider 
{
    private static CacheInterface $cache;

    public static function get(): CacheInterface
    {
        if (empty(self::$cache))
            self::$cache = new FileCache();
        return self::$cache;
    }

    public static function set(CacheInterface $cache): void
    {
        self::$cache = $cache;
    }
}
