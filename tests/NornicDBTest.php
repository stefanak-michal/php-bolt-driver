<?php

namespace Bolt\tests;

use Bolt\Bolt;
use Bolt\protocol\AProtocol;
use Bolt\protocol\v1\structures\{
    Duration,
};
use Bolt\enum\Signature;
use PHPUnit\Framework\TestCase;

/**
 * Class NornicDBTest
 * 
 * @author Michal Stefanak
 * @link https://github.com/neo4j-php/Bolt
 * @package Bolt\tests
 */
class NornicDBTest extends TestCase
{
    /**
     * @return AProtocol
     */
    public function testConnection(): AProtocol
    {
        if (!extension_loaded('sockets'))
            $this->markTestSkipped('Sockets extension not available');

        $conn = new \Bolt\connection\Socket('127.0.0.1', 7687, 3);
        $this->assertInstanceOf(\Bolt\connection\Socket::class, $conn);

        $bolt = new Bolt($conn);
        $this->assertInstanceOf(Bolt::class, $bolt);

        $protocol = $bolt->setProtocolVersions('4.4.4')->build();
        $this->assertInstanceOf(AProtocol::class, $protocol);

        $response = $protocol->hello(['scheme' => 'none'])->getResponse();
        $this->assertEquals(Signature::SUCCESS, $response->signature);

        return $protocol;
    }

    /**
     * Basic query test with basic data types
     * @depends testConnection
     * @param AProtocol $protocol
     */
    public function testQuery(AProtocol $protocol): void
    {
        $params = [
            'number' => 123,
            'string' => 'abc',
            'null' => null,
            'bool' => true,
            'float' => 0.4591563,
            'list' => [1, 2, 3],
        ];

        $query = implode(', ', array_map(function (string $key) {
            return '$' . $key . ' AS ' . $key;
        }, array_keys($params)));

        $runResponse = $protocol->run('RETURN ' . $query, $params)->getResponse();
        $this->assertEquals(Signature::SUCCESS, $runResponse->signature);

        $pullResponses = iterator_to_array($protocol->pull()->getResponses(), false);
        $this->assertCount(2, $pullResponses);
        $this->assertEquals(Signature::RECORD, $pullResponses[0]->signature);
        $this->assertEquals(Signature::SUCCESS, $pullResponses[1]->signature);

        $this->assertEquals($params, array_combine($runResponse->content['fields'], $pullResponses[0]->content));
    }
    /**
     * Test transaction handling
     * @depends testConnection
     * @param AProtocol $protocol
     */
    public function testTransaction(AProtocol $protocol): void
    {
        $this->clean($protocol);

        // create node inside transaction and rollback
        $res = iterator_to_array(
            $protocol
                ->begin()
                ->run('CREATE (a:Test) RETURN a')
                ->pull()
                ->rollback()
                ->getResponses(),
            false
        );

        $this->assertInstanceOf(\Bolt\protocol\v1\structures\Node::class, $res[2]->content[0]);
        // check if rollback was successful
        $this->assertEquals(Signature::SUCCESS, $res[4]->signature);

        // double check if creation of node was rolled back
        $res = iterator_to_array(
            $protocol
                ->run('MATCH (a:Test) WHERE ID(a) = $a RETURN COUNT(a)', [
                    'a' => $res[2]->content[0]->id
                ])
                ->pull()
                ->getResponses(),
            false
        );

        $this->assertEquals(0, $res[1]->content[0]);
    }

    /**
     * Test relationship structure
     * @depends testConnection
     * @param AProtocol $protocol
     */
    public function testRelationship(AProtocol $protocol): void
    {
        // clean up previous test data
        $this->clean($protocol);

        // create relationship
        $res = iterator_to_array(
            $protocol
                ->run('CREATE (:A)-[r:RELATES_TO]->(:B) RETURN r')
                ->pull()
                ->getResponses(),
            false
        );

        $this->assertInstanceOf(\Bolt\protocol\v1\structures\Relationship::class, $res[1]->content[0]);
    }

    /**
     * Test path structure
     * @depends testConnection
     * @param AProtocol $protocol
     */
    public function testPath(AProtocol $protocol): void
    {
        $this->clean($protocol);

        // create path
        $res = iterator_to_array(
            $protocol
                ->run('CREATE p=(:A)-[:RELATES_TO]->(:B) RETURN p')
                ->pull()
                ->getResponses(),
            false
        );

        $this->assertInstanceOf(\Bolt\protocol\v1\structures\Path::class, $res[1]->content[0]);
        $this->assertCount(2, $res[1]->content[0]->nodes);
        $this->assertCount(1, $res[1]->content[0]->relationships);
    }

    /**
     * Clean up database
     * @param AProtocol $protocol
     */
    private function clean(AProtocol $protocol): void
    {
        iterator_to_array(
            $protocol
                ->run('MATCH (n) DETACH DELETE n')
                ->pull()
                ->getResponses(),
            false
        );
    }
}
