<?php

namespace Bolt\tests\connection;

use Bolt\Bolt;
use Bolt\tests\TestLayer;
use Bolt\connection\{
    IConnection,
    PStreamSocket,
    Socket,
    StreamSocket
};
use Bolt\error\ConnectionTimeoutException;
use Bolt\enum\Signature;

/**
 * Class ConnectionTest
 *
 * @link https://github.com/neo4j-php/Bolt
 * @package Bolt\tests\connection
 */
final class ConnectionTest extends TestLayer
{
    public function provideConnections(): array
    {
        return [
            StreamSocket::class => [StreamSocket::class],
            Socket::class => [Socket::class],
        ];
    }

    /**
     * @dataProvider provideConnections
     */
    public function testMillisecondTimeout(string $alias): void
    {
        $conn = $this->getConnection($alias);
        $conn->setTimeout(1.5);
        $protocol = (new Bolt($conn))->setProtocolVersions($this->getCompatibleBoltVersion())->build();
        $this->sayHello($protocol, $GLOBALS['NEO_USER'], $GLOBALS['NEO_PASS']);
        $this->expectException(ConnectionTimeoutException::class);
        $protocol
            ->run('FOREACH ( i IN range(1,10000) | MERGE (d:Day {day: i}) )')
            ->getResponse();
    }

    /**
     * @dataProvider provideConnections
     */
    public function testLongNoTimeout(string $alias): void
    {
        $conn = $this->getConnection($alias);
        $protocol = (new Bolt($conn))->setProtocolVersions($this->getCompatibleBoltVersion())->build();
        $this->sayHello($protocol, $GLOBALS['NEO_USER'], $GLOBALS['NEO_PASS']);
        $conn->setTimeout(200);
        $protocol
            ->run('CALL apoc.util.sleep(150000)', [], ['mode' => 'r', 'tx_timeout' => 120000])
            ->getResponse();
    }

    /**
     * @dataProvider provideConnections
     */
    public function testSecondsTimeout(string $alias): void
    {
        $conn = $this->getConnection($alias);
        $conn->setTimeout(1);
        $protocol = (new Bolt($conn))->setProtocolVersions($this->getCompatibleBoltVersion())->build();
        $this->sayHello($protocol, $GLOBALS['NEO_USER'], $GLOBALS['NEO_PASS']);
        $this->expectException(ConnectionTimeoutException::class);
        $protocol
            ->run('FOREACH ( i IN range(1,10000) | MERGE (d:Day {day: i}) )')
            ->getResponse();
    }

    /**
     * @dataProvider provideConnections
     */
    public function testTimeoutRecoverAndReset(string $alias): void
    {
        $conn = $this->getConnection($alias);
        $protocol = (new Bolt($conn))->setProtocolVersions($this->getCompatibleBoltVersion())->build();
        $this->sayHello($protocol, $GLOBALS['NEO_USER'], $GLOBALS['NEO_PASS']);

        $conn->setTimeout(1.5);
        $time = microtime(true);
        try {
            iterator_to_array(
                $protocol
                    ->run('FOREACH ( i IN range(1,10000) | MERGE (d:Day {day: i}) )')
                    ->pull()
                    ->getResponses(),
                false);
            $this->fail('No timeout error triggered');
        } catch (ConnectionTimeoutException) {
            $newTime = microtime(true);
            $this->assertGreaterThanOrEqual(1.0, $newTime - $time);
        }

        $conn->setTimeout(15.0);
        $response = $protocol
            ->reset()
            ->getResponse();

        $this->assertEquals(Signature::FAILURE, $response->signature);
        $protocol = (new Bolt($conn))->setProtocolVersions($this->getCompatibleBoltVersion())->build();
        $this->sayHello($protocol, $GLOBALS['NEO_USER'], $GLOBALS['NEO_PASS']);

        $conn->setTimeout(1.5);
        $time = microtime(true);
        try {
            $protocol
                ->run('FOREACH ( i IN range(1,10000) | MERGE (d:Day {day: i}) )')
                ->getResponse();
            $this->fail('No timeout error triggered');
        } catch (ConnectionTimeoutException) {
            $newTime = microtime(true);
            $this->assertGreaterThanOrEqual(1.0, $newTime - $time);
        }
    }

    private function getConnection(string $class): IConnection
    {
        return new $class($GLOBALS['NEO_HOST'] ?? '127.0.0.1', (int)($GLOBALS['NEO_PORT'] ?? 7687), 1);
    }
}
