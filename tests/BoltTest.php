<?php

namespace Bolt\tests;

use Bolt\Bolt;
use Bolt\enum\Signature;
use Exception;
use Bolt\protocol\AProtocol;

/**
 * Class BoltTest
 *
 * @author Michal Stefanak
 * @link https://github.com/neo4j-php/Bolt
 * @package Bolt\tests
 */
class BoltTest extends TestLayer
{
    public function testSockets(): void
    {
        if (!extension_loaded('sockets'))
            $this->markTestSkipped('Sockets extension not available');

        $conn = new \Bolt\connection\Socket($GLOBALS['NEO_HOST'], $GLOBALS['NEO_PORT'], 3);
        $this->assertInstanceOf(\Bolt\connection\Socket::class, $conn);

        $bolt = new Bolt($conn);
        $this->assertInstanceOf(Bolt::class, $bolt);

        $protocol = $bolt->setProtocolVersions($this->getCompatibleBoltVersion())->build();
        $this->assertInstanceOf(AProtocol::class, $protocol);

        $this->sayHello($protocol, $GLOBALS['NEO_USER'], $GLOBALS['NEO_PASS']);

        if (method_exists($protocol, 'goodbye'))
            $protocol->goodbye();
    }

    public function testAura(): void
    {
        $conn = new \Bolt\connection\StreamSocket('neo4j+s://demo.neo4jlabs.com');
        $conn->setSslContextOptions([
            'verify_peer' => true
        ]);
        $this->assertInstanceOf(\Bolt\connection\StreamSocket::class, $conn);

        $bolt = new Bolt($conn);
        $this->assertInstanceOf(Bolt::class, $bolt);

        $protocol = $bolt->setProtocolVersions($this->getCompatibleBoltVersion('https://demo.neo4jlabs.com:7473'))->build();
        $this->assertInstanceOf(AProtocol::class, $protocol);

        $this->sayHello($protocol, 'movies', 'movies');

        if (method_exists($protocol, 'goodbye'))
            $protocol->goodbye();
    }

    public function testHello(): AProtocol
    {
        $conn = new \Bolt\connection\StreamSocket($GLOBALS['NEO_HOST'], $GLOBALS['NEO_PORT']);
        $this->assertInstanceOf(\Bolt\connection\StreamSocket::class, $conn);

        $bolt = new Bolt($conn);
        $this->assertInstanceOf(Bolt::class, $bolt);

        $protocol = $bolt->setProtocolVersions($this->getCompatibleBoltVersion())->build();
        $this->assertInstanceOf(AProtocol::class, $protocol);

        $this->sayHello($protocol, $GLOBALS['NEO_USER'], $GLOBALS['NEO_PASS']);

        return $protocol;
    }

    /**
     * @depends testHello
     */
    public function testPull(AProtocol $protocol): void
    {
        $protocol
            ->run('RETURN 1 AS num, 2 AS cnt', [], ['mode' => 'r'])
            ->pull();

        $this->assertArrayHasKey('fields', $protocol->getResponse()->content);

        $res = $protocol->getResponse()->content;
        $this->assertEquals(1, $res[0] ?? 0);
        $this->assertEquals(2, $res[1] ?? 0);
        $protocol->getResponse(); // last success message
    }

    /**
     * @depends testHello
     */
    public function testDiscard(AProtocol $protocol): void
    {
        $gen = $protocol
            ->run('MATCH (a:Test) RETURN *', [], ['mode' => 'r'])
            ->discard()
            ->getResponses();

        foreach ($gen as $response) {
            $this->assertEquals(Signature::SUCCESS, $response->signature);
        }
    }

    /**
     * @depends testHello
     * @throws Exception
     */
    public function testTransaction(AProtocol $protocol): void
    {
        if (version_compare($protocol->getVersion(), 3, '<')) {
            $this->markTestSkipped('Old Neo4j version does not support transactions');
        }

        $res = iterator_to_array(
            $protocol
                ->begin()
                ->run('CREATE (a:Test) RETURN a, ID(a)')
                ->pull()
                ->rollback()
                ->getResponses(),
            false
        );

        $id = $res[2]->content[1];
        $this->assertIsInt($id);

        $res = iterator_to_array(
            $protocol
                ->run('MATCH (a:Test) WHERE ID(a) = '
                    . (version_compare($protocol->getVersion(), 4, '<') ? '{a}' : '$a')
                    . ' RETURN COUNT(a)', [
                    'a' => $id
                ])
                ->pull()
                ->getResponses(),
            false
        );

        $this->assertEquals(0, $res[1]->content[0]);
    }

    /**
     * @depends testHello
     */
    public function testRoute(AProtocol $protocol): void
    {
        if (version_compare($protocol->getVersion(), 4.3, '>=')) {
            $response = $protocol
                ->route([
                    'address' => $GLOBALS['NEO_HOST'] . ':' . $GLOBALS['NEO_PORT']
                ])
                ->getResponse();
            $this->assertEquals(Signature::SUCCESS, $response->signature);
        } else {
            $this->markTestSkipped('Old Neo4j version does not support route message');
        }
    }

    /**
     * @depends testHello
     */
    public function testReset(AProtocol $protocol): void
    {
        $response = $protocol
            ->reset()
            ->getResponse();
        $this->assertEquals(Signature::SUCCESS, $response->signature);
    }

    /**
     * @large
     * @depends testHello
     * @throws Exception
     */
    public function testChunking(AProtocol $protocol): void
    {
        $gen = $protocol
            ->begin()
            ->run('CREATE (a:Test) RETURN ID(a)')
            ->pull()
            ->getResponses();
        $id = iterator_to_array($gen, false)[2]->content[0];

        $data = [];
        while (strlen(serialize($data)) < 65535 * 2) {
            $data[base64_encode(random_bytes(32))] = base64_encode(random_bytes(128));
            $gen = $protocol
                ->run('MATCH (a:Test) WHERE ID(a) = $id SET a += $data RETURN a', [
                    'id' => $id,
                    'data' => (object)$data
                ])
                ->pull()
                ->getResponses();
            $result = iterator_to_array($gen, false);
            $this->assertInstanceOf(\Bolt\protocol\v1\structures\Node::class, $result[1]->content[0]);
            $this->assertCount(count($data), $result[1]->content[0]->properties);
        }

        $protocol->rollback();
    }
}
