<?php

namespace Bolt\connection;

use Bolt\Bolt;
use Bolt\error\ConnectException;
use Bolt\error\ConnectionTimeoutException;

/**
 * Socket class
 *
 * @author Michal Stefanak
 * @link https://github.com/neo4j-php/Bolt
 * @package Bolt\connection
 */
class Socket extends AConnection
{
    /**
     * @var \Socket|bool
     */
    private $socket = false;

    private const POSSIBLE_RETRY_CODES = [
        SOCKET_EINTR,
        SOCKET_EWOULDBLOCK
    ];

    public function __construct(string $ip = '127.0.0.1', int $port = 7687, float $timeout = 15)
    {
        if (!extension_loaded('sockets')) {
            throw new ConnectException('PHP Extension sockets not enabled');
        }
        parent::__construct($ip, $port, $timeout);
    }

    public function connect(): bool
    {
        $this->socket = @socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if ($this->socket === false) {
            throw new ConnectException('Cannot create socket');
        }

        if (socket_set_block($this->socket) === false) {
            throw new ConnectException('Cannot set socket into blocking mode');
        }

        socket_set_option($this->socket, SOL_TCP, TCP_NODELAY, 1);
        socket_set_option($this->socket, SOL_SOCKET, SO_KEEPALIVE, 1);
        $this->configureTimeout();

        $start = microtime(true);
        if (!@socket_connect($this->socket, $this->ip, $this->port)) {
            $this->throwConnectException($start);
        }

        return true;
    }

    public function write(string $buffer): void
    {
        if ($this->socket === false) {
            throw new ConnectException('Not initialized socket');
        }

        if (Bolt::$debug) {
            $this->printHex($buffer);
        }

        $start = microtime(true);
        $size = mb_strlen($buffer, '8bit');
        while (0 < $size) {
            $sent = @socket_write($this->socket, $buffer, $size);
            if ($sent === false || $sent === 0) {
                if (in_array(socket_last_error($this->socket), self::POSSIBLE_RETRY_CODES, true)) {
                    continue;
                }
                $this->throwConnectException($start);
            }

            $buffer = mb_strcut($buffer, $sent, null, '8bit');
            $size -= $sent;
        }
    }

    public function read(int $length = 2048): string
    {
        if ($this->socket === false) {
            throw new ConnectException('Not initialized socket');
        }

        $output = '';
        $start = microtime(true);
        do {
            $readed = '';
            $result = @socket_recv($this->socket, $readed, $length - mb_strlen($output, '8bit'), 0);
            if ($result === false) {
                if (in_array(socket_last_error($this->socket), self::POSSIBLE_RETRY_CODES, true)) {
                    continue;
                }
                $this->throwConnectException($start);
            } elseif ($result === 0) {
                throw new ConnectException('Connection closed by remote host');
            }
            $output .= $readed;
        } while (mb_strlen($output, '8bit') < $length);

        if (Bolt::$debug) {
            $this->printHex($output, 'S: ');
        }

        return $output;
    }

    public function disconnect(): void
    {
        if ($this->socket !== false) {
            @socket_shutdown($this->socket);
            @socket_close($this->socket);
        }
    }

    public function setTimeout(float $timeout): void
    {
        parent::setTimeout($timeout);
        $this->configureTimeout();
    }

    private function configureTimeout(): void
    {
        if ($this->socket === false) {
            return;
        }
        $timeoutSeconds = (int)floor($this->timeout);
        $microSeconds = (int)floor(($this->timeout - $timeoutSeconds) * 1000000);
        $timeoutOption = ['sec' => $timeoutSeconds, 'usec' => $microSeconds];
        socket_set_option($this->socket, SOL_SOCKET, SO_RCVTIMEO, $timeoutOption);
        socket_set_option($this->socket, SOL_SOCKET, SO_SNDTIMEO, $timeoutOption);
    }

    /**
     * Throws an exception based on the last socket error or timeout.
     * @param float|null $start
     * @throws ConnectException
     * @throws ConnectionTimeoutException
     */
    private function throwConnectException(float|null $start = null): void
    {
        $code = socket_last_error($this->socket);
        if ($code === SOCKET_ETIMEDOUT) {
            throw new ConnectionTimeoutException('Connection timeout reached after ' . $this->timeout . ' seconds.');
        } elseif ($code !== 0) {
            throw new ConnectException(socket_strerror($code), $code);
        } elseif ($start !== null && $this->timeout > 0 && (microtime(true) - $start) >= $this->timeout) {
            throw new ConnectionTimeoutException('Connection timeout reached after ' . $this->timeout . ' seconds.');
        }
    }
}
