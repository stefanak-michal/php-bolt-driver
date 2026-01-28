<?php

namespace Bolt\tests\error;

use PHPUnit\Framework\TestCase;

/**
 * Class ErrorsTest
 *
 * @author Michal Stefanak
 * @link https://github.com/neo4j-php/Bolt
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

    public function testPackException1(): void
    {
        $packer = new \Bolt\packstream\v1\Packer();
        $this->assertInstanceOf(\Bolt\packstream\v1\Packer::class, $packer);
        $this->expectException(\Bolt\error\PackException::class);
        foreach ($packer->pack(0x00, fopen('php://input', 'r')) as $chunk) {
            $this->markTestIncomplete();
        }
    }

    public function testPackException2(): void
    {
        $conn = new \Bolt\connection\StreamSocket($GLOBALS['NEO_HOST'], $GLOBALS['NEO_PORT']);
        $this->assertInstanceOf(\Bolt\connection\StreamSocket::class, $conn);

        $bolt = new \Bolt\Bolt($conn);
        $this->assertInstanceOf(\Bolt\Bolt::class, $bolt);

        $this->expectException(\Bolt\error\PackException::class);
        $bolt->setPackStreamVersion(2);
        $bolt->build();
    }
}
