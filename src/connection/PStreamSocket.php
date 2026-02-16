<?php


namespace Bolt\connection;

use Psr\SimpleCache\CacheInterface;
use Bolt\helpers\CacheProvider;

/**
 * Persistent stream socket class
 *
 * Because PHP is stateless, using this connection class requires storing meta information about active TCP connection.
 * Default storage is FileCache which you can change with `setCache`.
 *
 * If your system reuse persistent connection and meta information about it was lost for some reason,
 * your attemt to connect will end with ConnectionTimeoutException.
 *
 * @author Michal Stefanak
 * @link https://github.com/stefanak-michal/php-bolt-driver
 * @package Bolt\connection
 */
class PStreamSocket extends StreamSocket
{
    private string $identifier;

    protected int $connectionFlags = STREAM_CLIENT_CONNECT | STREAM_CLIENT_PERSISTENT;

    /**
     * @deprecated Cache is no longer held in this class. Use CacheProvider::set() instead.
     */
    public function setCache(CacheInterface $cache): void
    {
        CacheProvider::set($cache);
    }

    /**
     * @deprecated Cache is no longer held in this class. Use CacheProvider::get() instead.
     */
    public function getCache(): CacheInterface
    {
        return CacheProvider::get();
    }

    public function connect(): bool
    {
        $result = parent::connect();

        //dump TCP buffer leftovers
        $read = [$this->stream];
        $write = $except = null;
        if (stream_select($read, $write, $except, 0) === 1) {
            do {
                $r = fread($this->stream, 1024);
            } while ($r !== false && mb_strlen($r) == 1024);
        }

        return $result;
    }

    public function getIdentifier(): string
    {
        if (empty($this->identifier))
            $this->identifier = str_replace(':', '_', stream_socket_get_name($this->stream, false)) . '_' . str_replace(':', '_', stream_socket_get_name($this->stream, true));
        return $this->identifier;
    }

    public function disconnect(): void
    {
        if (is_resource($this->stream)) {
            stream_socket_shutdown($this->stream, STREAM_SHUT_RDWR);
            fclose($this->stream);
            unset($this->stream);
            CacheProvider::get()->delete($this->getIdentifier());
        }
    }
}
