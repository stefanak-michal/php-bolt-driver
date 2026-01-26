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
    public function testSetBlockingException(): void
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

    public function testCompareBlockingAndNonBlocking(): void
    {
        // Test blocking connection
        $connBlocking = new \Bolt\connection\Socket($GLOBALS['NEO_HOST'], $GLOBALS['NEO_PORT']);
        $protocolBlocking = (new \Bolt\Bolt($connBlocking))->setProtocolVersions($this->getCompatibleBoltVersion())->build();
        $this->sayHello($protocolBlocking, $GLOBALS['NEO_USER'], $GLOBALS['NEO_PASS']);
        
        $startBlocking = microtime(true);
        $responseBlocking = $protocolBlocking
            ->run('RETURN 1 AS number')
            ->getResponse();
        $timeBlocking = microtime(true) - $startBlocking;
        
        // Test non-blocking connection
        $connNonBlocking = new \Bolt\connection\Socket($GLOBALS['NEO_HOST'], $GLOBALS['NEO_PORT']);
        $connNonBlocking->setBlocking(false);
        $protocolNonBlocking = (new \Bolt\Bolt($connNonBlocking))->setProtocolVersions($this->getCompatibleBoltVersion())->build();
        $this->sayHello($protocolNonBlocking, $GLOBALS['NEO_USER'], $GLOBALS['NEO_PASS']);
        
        $startNonBlocking = microtime(true);
        $responseNonBlocking = $protocolNonBlocking
            ->run('RETURN 1 AS number')
            ->getResponse();
        $timeNonBlocking = microtime(true) - $startNonBlocking;

        // Assert both return the same result
        $this->assertEquals(Signature::SUCCESS, $responseBlocking->signature);
        $this->assertEquals(Signature::SUCCESS, $responseNonBlocking->signature);

        // Assert non-blocking is not slower than blocking, it should be faster because of reduced wait times
        $this->assertGreaterThan($timeNonBlocking, $timeBlocking);
    }
}
