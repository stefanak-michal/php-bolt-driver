<?php

namespace Bolt\connection;

use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Psr\Log\LoggerAwareInterface;

/**
 * Class AConnection
 *
 * @author Michal Stefanak
 * @link https://github.com/stefanak-michal/php-bolt-driver
 * @package Bolt\connection
 */
abstract class AConnection implements IConnection, LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function __construct(
        protected string $ip = '127.0.0.1',
        protected int    $port = 7687,
        protected float  $timeout = 15
    )
    {
        if (filter_var($this->ip, FILTER_VALIDATE_URL)) {
            $scheme = parse_url($this->ip, PHP_URL_SCHEME);
            if (!empty($scheme)) {
                $this->ip = str_replace($scheme . '://', '', $this->ip);
            }
        }
        $this->logger = new NullLogger();
    }

    public function getIp(): string
    {
        return $this->ip;
    }

    public function getPort(): int
    {
        return $this->port;
    }

    public function getTimeout(): float
    {
        return $this->timeout;
    }

    public function setTimeout(float $timeout): void
    {
        $this->timeout = $timeout;
    }
}
