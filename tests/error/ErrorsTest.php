<?php

namespace Bolt\tests\error;

use PHPUnit\Framework\TestCase;

/**
 * Class ErrorsTest
 *
 * @author Michal Stefanak
 * @link https://github.com/stefanak-michal/php-bolt-driver
 * @package Bolt\tests
 */
class ErrorsTest extends TestCase
{
    public function testConnectException(): void
    {
        $conn = new \Bolt\connection\StreamSocket('127.0.0.1', 7800, 1);
        $this->assertInstanceOf(\Bolt\connection\StreamSocket::class, $conn);
        $this->expectException(\Bolt\error\ConnectException::class);
        $conn->connect();
    }

    public function testPackException(): void
    {
        $packer = new \Bolt\packstream\v1\Packer();
        $this->assertInstanceOf(\Bolt\packstream\v1\Packer::class, $packer);
        $this->expectException(\Bolt\error\PackException::class);
        foreach ($packer->pack(0x00, fopen('php://input', 'r')) as $chunk) {
            $this->markTestIncomplete();
        }
    }

    public function testUnpackException(): void
    {
        $unpacker = new \Bolt\packstream\v1\Unpacker();
        $this->assertInstanceOf(\Bolt\packstream\v1\Unpacker::class, $unpacker);
        $this->expectException(\Bolt\error\UnpackException::class);
        $unpacker->unpack(chr(0xE0) . str_repeat("\0", 16));
    }
}
