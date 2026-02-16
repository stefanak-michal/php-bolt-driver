<?php

namespace Bolt\tests\helpers;

use PHPUnit\Framework\TestCase;
use Bolt\helpers\FileCache;

/**
 * Class FileCacheTest
 *
 * @author Michal Stefanak
 * @link https://github.com/stefanak-michal/php-bolt-driver
 * @package Bolt\tests\helpers
 */
class FileCacheTest extends TestCase
{
    private FileCache $cache;

    protected function setUp(): void
    {
        $this->cache = new FileCache();
    }

    public function testConstruct(): void
    {
        $this->assertDirectoryExists(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'php-bolt-filecache');
        $this->assertDirectoryExists(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'php-bolt-filecache' . DIRECTORY_SEPARATOR . '.ttl');
    }

    public function testGetAndSet(): void
    {
        $key = uniqid('key_', true);
        $value = uniqid('value_', true);
        $this->assertTrue($this->cache->set($key, $value));
        $this->assertEquals($value, $this->cache->get($key));
    }

    public function testDelete(): void
    {
        $key = uniqid('key_', true);
        $value = uniqid('value_', true);
        $this->assertTrue($this->cache->set($key, $value));
        $this->assertTrue($this->cache->delete($key));
        $this->assertNull($this->cache->get($key));
    }

    public function testClear(): void
    {
        $this->assertTrue($this->cache->set(uniqid('key_', true), uniqid('value_', true)));
        $this->assertTrue($this->cache->set(uniqid('key_', true), uniqid('value_', true)));
        $this->assertTrue($this->cache->clear());
        $this->assertNull($this->cache->get(uniqid('key_', true)));
        $this->assertNull($this->cache->get(uniqid('key_', true)));
    }

    public function testGetMultiple(): void
    {
        $key1 = uniqid('key1_', true);
        $key2 = uniqid('key2_', true);
        $value1 = uniqid('value1_', true);
        $value2 = uniqid('value2_', true);
        $this->assertTrue($this->cache->set($key1, $value1));
        $this->assertTrue($this->cache->set($key2, $value2));
        $result = iterator_to_array($this->cache->getMultiple([$key1, $key2]));
        $this->assertEquals([$value1, $value2], $result);
    }

    public function testSetMultiple(): void
    {
        $values = [
            uniqid('key1_', true) => uniqid('value1_', true),
            uniqid('key2_', true) => uniqid('value2_', true)
        ];
        $this->assertTrue($this->cache->setMultiple($values));
        foreach ($values as $key => $value) {
            $this->assertEquals($value, $this->cache->get($key));
        }
    }

    public function testDeleteMultiple(): void
    {
        $key1 = uniqid('key1_', true);
        $key2 = uniqid('key2_', true);
        $value1 = uniqid('value1_', true);
        $value2 = uniqid('value2_', true);
        $this->assertTrue($this->cache->set($key1, $value1));
        $this->assertTrue($this->cache->set($key2, $value2));
        $this->assertTrue($this->cache->deleteMultiple([$key1, $key2]));
        $this->assertNull($this->cache->get($key1));
        $this->assertNull($this->cache->get($key2));
    }

    public function testHas(): void
    {
        $key = uniqid('key_', true);
        $value = uniqid('value_', true);
        $this->assertTrue($this->cache->set($key, $value));
        $this->assertTrue($this->cache->has($key));
        $this->assertTrue($this->cache->delete($key));
        $this->assertFalse($this->cache->has($key));
    }

    public function testLockAndUnlock(): void
    {
        $key = uniqid('key_', true);
        $value = uniqid('value_', true);
        $this->assertTrue($this->cache->set($key, $value));
        $this->assertTrue($this->cache->lock($key));
        $this->cache->unlock($key);
        
        $reflection = new \ReflectionClass($this->cache);
        $property = $reflection->getProperty('handles');
        $property->setAccessible(true);
        $handles = $property->getValue($this->cache);
        $this->assertFalse(array_key_exists($key, $handles));
    }

    public function testLockingMechanism(): void
    {
        $key = 'test_lock_key';
        $this->assertTrue($this->cache->delete($key));

        $descriptorspec = array(
            0 => array("pipe", "r"),
            1 => array("pipe", "w"),
        );

        $t = microtime(true);
        // run another script in background
        $proc = proc_open('php ' . __DIR__ . DIRECTORY_SEPARATOR . 'lock.php', $descriptorspec, $pipes);
        // wait to make sure another script is running and it locked the key
        sleep(1);
        proc_close($proc);
        
        if ($this->cache->lock($key)) {
            $this->assertGreaterThan(3.0, microtime(true) - $t);
            $this->assertEquals(123, $this->cache->get($key));
            $this->cache->unlock($key);
        }
    }

    public function testShutdown(): void
    {
        $key = 'test_lock_key';
        $this->assertTrue($this->cache->delete($key));

        $descriptorspec = array(
            0 => array("pipe", "r"),
            1 => array("pipe", "w"),
        );

        $t = microtime(true);
        // Run another script in background
        $proc = proc_open('php ' . __DIR__ . DIRECTORY_SEPARATOR . 'lock.php', $descriptorspec, $pipes);
        $pid = proc_get_status($proc)['pid'];
        sleep(1);

        // Terminate the process
        if (strncasecmp(PHP_OS, 'WIN', 3) === 0) {
            exec("taskkill /F /PID $pid /T");
        } else {
            exec("kill -9 $pid");
        }

        proc_close($proc);

        $this->assertLessThan(3.0, microtime(true) - $t);
        $this->assertTrue($this->cache->has($key));
        $this->assertNull($this->cache->get($key));
        $this->assertTrue($this->cache->lock($key));
        $this->cache->unlock($key);
    }

    public function testInvalidKey(): void
    {
        $this->expectException(\Psr\SimpleCache\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid cache key: invalid key!. Allowed characters are A-Za-z0-9_.');
        $this->cache->set('invalid key!', 'value');
    }
}
