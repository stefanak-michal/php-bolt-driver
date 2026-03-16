<?php

namespace Bolt\helpers;

use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException;

/**
 * Class FileCache
 * implementation of PSR-16 Simple Cache Interface
 *
 * @author Michal Stefanak
 * @link https://github.com/stefanak-michal/php-bolt-driver
 * @package Bolt\helpers
 */
class FileCache implements CacheInterface
{
    private string $tempDir;
    /**
     * @var resource[]
     */
    private array $handles = [];

    public function __construct()
    {
        $this->tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'php-bolt-filecache' . DIRECTORY_SEPARATOR;
        
        if (!file_exists($this->tempDir)) {
            mkdir(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'php-bolt-filecache', recursive: true);
        }
        // dotted directory to hold "time-to-live" informations
        if (!file_exists($this->tempDir . '.ttl' . DIRECTORY_SEPARATOR)) {
            mkdir($this->tempDir . '.ttl');
        }

        register_shutdown_function([$this, 'shutdown']);
    }

    private function shutdown(): void
    {
        foreach ($this->handles as $handle) {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
        $this->handles = [];
    }

    /**
     * Validate cache key if it does conform to allowed characters
     */
    private function validateKey(string $key): void
    {
        if (!preg_match('/^[\w\.]+$/i', $key)) {
            throw new class($key) extends \Exception implements InvalidArgumentException {
                protected $message;

                public function __construct(string $key)
                {
                    $this->message = "Invalid cache key: $key. Allowed characters are A-Za-z0-9_.";
                }
            };
        }
    }

    /**
     * @inheritDoc
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $this->validateKey($key);

        if (array_key_exists($key, $this->handles)) {
            rewind($this->handles[$key]);
            return @unserialize(stream_get_contents($this->handles[$key]), ['allowed_classes' => false]);
        }

        if ($this->has($key)) {
            $data = file_get_contents($this->tempDir . $key);
            if (!empty($data)) {
                return @unserialize($data, ['allowed_classes' => false]);
            }
        }

        return $default;
    }

    /**
     * @inheritDoc
     */
    public function set(string $key, mixed $value, \DateInterval|int|null $ttl = null): bool
    {
        $this->validateKey($key);

        if ($ttl) {
            is_writable($this->tempDir . '.ttl' . DIRECTORY_SEPARATOR) && file_put_contents(
                $this->tempDir . '.ttl' . DIRECTORY_SEPARATOR . $key,
                $ttl instanceof \DateInterval ? (new \DateTime())->add($ttl)->getTimestamp() : $ttl
            );
        }

        if (array_key_exists($key, $this->handles)) {
            ftruncate($this->handles[$key], 0);
            rewind($this->handles[$key]);
            return fwrite($this->handles[$key], serialize($value)) !== false;
        }

        return is_writable($this->tempDir) && file_put_contents($this->tempDir . $key, serialize($value)) !== false;
    }

    /**
     * @inheritDoc
     */
    public function delete(string $key): bool
    {
        $this->validateKey($key);

        if ($this->has($key)) {
            if (file_exists($this->tempDir . '.ttl' . DIRECTORY_SEPARATOR . $key)) {
                @unlink($this->tempDir . '.ttl' . DIRECTORY_SEPARATOR . $key);
            }
            return @unlink($this->tempDir . $key);
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function clear(): bool
    {
        array_map('unlink', glob($this->tempDir . '*.*'));
        array_map('unlink', glob($this->tempDir . '.ttl' . DIRECTORY_SEPARATOR . '*.*'));
        return true;
    }

    /**
     * @inheritDoc
     */
    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        foreach ($keys as $key) {
            yield $this->get($key, $default);
        }
    }

    /**
     * @inheritDoc
     */
    public function setMultiple(iterable $values, \DateInterval|int|null $ttl = null): bool
    {
        foreach ($values as $key => $value) {
            $this->set($key, $value, $ttl);
        }
        return true;
    }

    /**
     * @inheritDoc
     */
    public function deleteMultiple(iterable $keys): bool
    {
        foreach ($keys as $key) {
            $this->delete($key);
        }
        return true;
    }

    /**
     * @inheritDoc
     */
    public function has(string $key): bool
    {
        $this->validateKey($key);
        
        // remove file when is expired
        if (
            file_exists($this->tempDir . '.ttl' . DIRECTORY_SEPARATOR . $key)
            && intval(file_get_contents($this->tempDir . '.ttl' . DIRECTORY_SEPARATOR . $key)) < time()
        ) {
            @unlink($this->tempDir . '.ttl' . DIRECTORY_SEPARATOR . $key);
            @unlink($this->tempDir . $key);
        }

        return file_exists($this->tempDir . $key) && is_file($this->tempDir . $key);
    }

    /**
     * Lock a key to prevent other processes from modifying it
     */
    public function lock(string $key): bool
    {
        $this->validateKey($key);

        $handle = @fopen($this->tempDir . $key, 'c+');
        if ($handle === false) return false;
        $this->handles[$key] = $handle;
        return flock($this->handles[$key], LOCK_EX);
    }

    /**
     * Unlock a key
     */
    public function unlock(string $key): void
    {
        $this->validateKey($key);

        if (array_key_exists($key, $this->handles)) {
            flock($this->handles[$key], LOCK_UN);
            fclose($this->handles[$key]);
            unset($this->handles[$key]);
        }
    }

    public function __destruct()
    {
        $this->shutdown();
    }
}
