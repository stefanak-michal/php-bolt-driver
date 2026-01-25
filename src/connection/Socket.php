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

    private const POSSIBLE_TIMEOUTS_CODES = [11, 10060];
    private const POSSIBLE_RETRY_CODES = [4, 10004];
    private const POSSIBLE_CONNECT_IN_PROGRESS_CODES = [
        11,     // EAGAIN/EWOULDBLOCK (common on *nix)
        115,    // EINPROGRESS
        114,    // EALREADY
        10035,  // WSAEWOULDBLOCK
        10036,  // WSAEINPROGRESS
    ];

    /**
     * You can set socket into blocking or non-blocking mode - default is blocking
     * @var bool
     */
    public bool $blocking = true;

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

        if ($this->blocking) {
            if (socket_set_block($this->socket) === false) {
                throw new ConnectException('Cannot set socket into blocking mode');
            }
        } else {
            if (socket_set_nonblock($this->socket) === false) {
                throw new ConnectException('Cannot set socket into non-blocking mode');
            }
        }

        socket_set_option($this->socket, SOL_TCP, TCP_NODELAY, 1);
        socket_set_option($this->socket, SOL_SOCKET, SO_KEEPALIVE, 1);
        $this->configureTimeout();

        $start = microtime(true);
        $conn = @socket_connect($this->socket, $this->ip, $this->port);
        if (!$conn) {
            $code = socket_last_error($this->socket);
            if (!$this->blocking && in_array($code, self::POSSIBLE_CONNECT_IN_PROGRESS_CODES, true)) {
                $this->waitForWritable($start);
                $soError = socket_get_option($this->socket, SOL_SOCKET, SO_ERROR);
                if ($soError === 0) {
                    socket_clear_error($this->socket);
                    return true;
                }
            }

            $this->throwConnectException();
        }

        return true;
    }

    /**
     * Wait for the non-blocking socket to become writable within the timeout period.
     * @param float $startTime
     * @throws ConnectException
     * @throws ConnectionTimeoutException
     */
    private function waitForWritable(float $startTime): void
    {
        $seconds = null;
        $microseconds = 0;

        if ($this->timeout > 0) {
            $remaining = $this->timeout - (microtime(true) - $startTime);
            if ($remaining <= 0) {
                throw new ConnectionTimeoutException('Connection timeout reached after ' . $this->timeout . ' seconds.');
            }
            $seconds = (int)floor($remaining);
            $microseconds = (int)floor(($remaining - $seconds) * 1000000);
        }

        $readArr = null;
        $writeArr = [$this->socket];
        $exceptArr = [$this->socket];
        $selectResult = @socket_select($readArr, $writeArr, $exceptArr, $seconds, $microseconds);

        if ($selectResult === false || $selectResult === 0) {
            $this->throwConnectException($startTime);
        }
    }

    /**
     * Wait for the non-blocking socket to become readable within the timeout period.
     * @param float $startTime
     * @throws ConnectException
     * @throws ConnectionTimeoutException
     */
    private function waitForReadable(float $startTime): void
    {
        $seconds = null;
        $microseconds = 0;

        if ($this->timeout > 0) {
            $remaining = $this->timeout - (microtime(true) - $startTime);
            if ($remaining <= 0) {
                throw new ConnectionTimeoutException('Connection timeout reached after ' . $this->timeout . ' seconds.');
            }
            $seconds = (int)floor($remaining);
            $microseconds = (int)floor(($remaining - $seconds) * 1000000);
        }

        $readArr = [$this->socket];
        $writeArr = null;
        $exceptArr = null;
        $selectResult = @socket_select($readArr, $writeArr, $exceptArr, $seconds, $microseconds);

        if ($selectResult === false || $selectResult === 0) {
            $this->throwConnectException($startTime);
        }
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
            if (!$this->blocking) {
                $this->waitForWritable($start);
            }

            $sent = @socket_write($this->socket, $buffer, $size);
            if ($sent === false || $sent === 0) {
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
            // this will be handled in throwConnectException
            // if (mb_strlen($output, '8bit') == 0 && $this->timeout > 0 && (microtime(true) - $start) >= $this->timeout)
            //     throw new ConnectionTimeoutException('Read from connection reached timeout after ' . $this->timeout . ' seconds.');

            if (!$this->blocking) {
                $this->waitForReadable($start);
            }

            $readed = @socket_read($this->socket, $length - mb_strlen($output, '8bit'));
            if ($readed === false || mb_strlen($readed, '8bit') === 0) {
                if (in_array(socket_last_error($this->socket), self::POSSIBLE_RETRY_CODES, true)) {
                    continue;
                }
                $this->throwConnectException($start);
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
        if (in_array($code, self::POSSIBLE_TIMEOUTS_CODES, true)) {
            throw new ConnectionTimeoutException('Connection timeout reached after ' . $this->timeout . ' seconds.');
        } elseif ($code !== 0) {
            throw new ConnectException(socket_strerror($code), $code);
        } elseif ($start !== null && $this->timeout > 0 && (microtime(true) - $start) >= $this->timeout) {
            throw new ConnectionTimeoutException('Connection timeout reached after ' . $this->timeout . ' seconds.');
        }
    }
}
