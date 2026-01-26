<?php

namespace Bolt\tests\connection;

use Bolt\enum\Signature;
use Bolt\tests\TestLayer;

/**
 * Class SocketNonBlockingTest
 *
 * @link https://github.com/neo4j-php/Bolt
 * @package Bolt\tests\connection
 */
final class SocketNonBlockingTest extends TestLayer
{
    public function testSetBlockingAfterConnect(): void
    {
        $this->expectException(\Bolt\error\ConnectException::class);
        $conn = new \Bolt\connection\Socket($GLOBALS['NEO_HOST'], $GLOBALS['NEO_PORT']);
        $protocol = (new \Bolt\Bolt($conn))->setProtocolVersions($this->getCompatibleBoltVersion())->build();
        $conn->setBlocking(false);
    }

    public function testNonBlockingMode(): void
    {
        $conn = new \Bolt\connection\Socket($GLOBALS['NEO_HOST'], $GLOBALS['NEO_PORT']);
        $conn->setBlocking(false);
        $protocol = (new \Bolt\Bolt($conn))->setProtocolVersions($this->getCompatibleBoltVersion())->build();
        $this->sayHello($protocol, $GLOBALS['NEO_USER'], $GLOBALS['NEO_PASS']);
        $response = $protocol
            ->run('RETURN 1 AS number')
            ->getResponse();
        $this->assertEquals(Signature::SUCCESS, $response->signature);
    }
}
