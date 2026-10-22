<?php

namespace Bolt\tests;

use Bolt\protocol\IStructure;
use Bolt\Bolt;
use Bolt\protocol\AProtocol;
use Bolt\protocol\v1\structures\{
    Date,
    Duration,
    LocalTime,
    LocalDateTime
};
use Bolt\enum\Signature;
use PHPUnit\Framework\TestCase;

/**
 * Class MemgraphTest
 * 
 * @author Michal Stefanak
 * @link https://github.com/stefanak-michal/php-bolt-driver
 * @package Bolt\tests
 */
class MemgraphTest extends TestCase
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

        $protocol = $bolt->setProtocolVersions(5.2, 4.3, 4.1, 4.0)->build();
        $this->assertInstanceOf(AProtocol::class, $protocol);

        if (version_compare($protocol->getVersion(), '5.2', '>=')) {
            $protocol->hello()->getResponse();
            $protocol->logon(['scheme' => 'none'])->getResponse();
        } else {
            $protocol->hello(['scheme' => 'none'])->getResponse();
        }

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
            'dictionary' => ['a' => 1, 'b' => 2, 'c' => 3]
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
        if (version_compare($protocol->getVersion(), 3, '<')) {
            $this->markTestSkipped('Old Memgraph version does not support transactions');
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
     * Test additional data types
     * @depends testConnection
     * @dataProvider structureProvider
     * @param IStructure $structure
     * @param AProtocol $protocol
     */
    public function testStructure(IStructure $structure, AProtocol $protocol): void
    {
        $responses = iterator_to_array(
            $protocol
                ->run('RETURN $s', [
                    's' => $structure
                ])
                ->pull()
                ->getResponses(),
            false
        );

        $this->assertInstanceOf(get_class($structure), $responses[1]->content[0]);
        $this->assertEquals((string)$structure, (string)$responses[1]->content[0]);
    }

    public function structureProvider(): \Generator
    {
        yield 'Duration' => [new Duration(0, 4, 3, 2)];
        yield 'Date' => [new Date(intval(floor(time() / 86400)))];
        yield 'LocalTime' => [new LocalTime(intval(microtime(true) * 1000))];
        yield 'LocalDateTime' => [new LocalDateTime(time(), 1234)];
    }
}
