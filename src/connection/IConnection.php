<?php

namespace Bolt\connection;

use Bolt\error\ConnectException;

/**
 * Interface IConnection
 *
 * @author Michal Stefanak
 * @link https://github.com/stefanak-michal/php-bolt-driver
 * @package Bolt\connection
 */
interface IConnection
{
    public function __construct(string $ip = '127.0.0.1', int $port = 7687, float $timeout = 15);

    /**
     * @throws ConnectException
     */
    public function connect(): bool;

    /**
     * @throws ConnectException
     */
    public function write(string $buffer): void;

    /**
     * @throws ConnectException
     */
    public function read(int $length = 2048): string;

    public function disconnect(): void;

    public function getIp(): string;

    public function getPort(): int;

    public function getTimeout(): float;

    public function setTimeout(float $timeout): void;
}
