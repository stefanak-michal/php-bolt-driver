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
     * @var resource|\Socket|bool
     */
    private $socket = false;

    private const POSSIBLE_TIMEOUTS_CODES = [11, 10060];

    public function connect(): bool
    {
        if (!extension_loaded('sockets')) {
            throw new ConnectException('PHP Extension sockets not enabled');
        }

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

        $conn = @socket_connect($this->socket, $this->ip, $this->port);
        if (!$conn) {
            $code = socket_last_error($this->socket);
            throw new ConnectException(socket_strerror($code), $code);
        }

        return true;
    }

    public function write(string $buffer): void
    {
        if ($this->socket === false) {
            throw new ConnectException('Not initialized socket');
        }

        if (Bolt::$debug)
            $this->printHex($buffer);

        $size = mb_strlen($buffer, '8bit');
        while (0 < $size) {
            $sent = @socket_write($this->socket, $buffer, $size);
            if ($sent === false)
                $this->throwConnectException();
            $buffer = mb_strcut($buffer, $sent, null, '8bit');
            $size -= $sent;
        }
    }

    public function read(int $length = 2048): string
    {
        if ($this->socket === false)
            throw new ConnectException('Not initialized socket');

        $output = '';
        $t = microtime(true);
        do {
            if (mb_strlen($output, '8bit') == 0 && $this->timeout > 0 && (microtime(true) - $t) >= $this->timeout)
                throw new ConnectionTimeoutException('Read from connection reached timeout after ' . $this->timeout . ' seconds.');
            $readed = @socket_read($this->socket, $length - mb_strlen($output, '8bit'));
            if ($readed === false) {
                if (socket_last_error($this->socket) === 10004)
                    continue;
                $this->throwConnectException();
            }
            $output .= $readed;
        } while (mb_strlen($output, '8bit') < $length);

        if (Bolt::$debug)
            $this->printHex($output, 'S: ');

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
        if ($this->socket === false)
            return;
        $timeoutSeconds = floor($this->timeout);
        $microSeconds = floor(($this->timeout - $timeoutSeconds) * 1000000);
        $timeoutOption = ['sec' => $timeoutSeconds, 'usec' => $microSeconds];
        socket_set_option($this->socket, SOL_SOCKET, SO_RCVTIMEO, $timeoutOption);
        socket_set_option($this->socket, SOL_SOCKET, SO_SNDTIMEO, $timeoutOption);
    }

    /**
     * @throws ConnectException
     * @throws ConnectionTimeoutException
     */
    private function throwConnectException(): void
    {
        $code = socket_last_error($this->socket);
        if (in_array($code, self::POSSIBLE_TIMEOUTS_CODES)) {
            throw new ConnectionTimeoutException('Connection timeout reached after ' . $this->timeout . ' seconds.');
        } elseif ($code !== 0) {
            throw new ConnectException(socket_strerror($code), $code);
        }
    }
}
