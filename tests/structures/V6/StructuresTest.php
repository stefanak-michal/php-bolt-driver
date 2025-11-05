<?php

namespace Bolt\tests\structures\v6;

use Bolt\Bolt;
use Bolt\protocol\AProtocol;
use Bolt\protocol\v6\structures\Vector;

/**
 * Class StructuresTest
 *
 * @author Michal Stefanak
 * @link https://github.com/neo4j-php/Bolt
 * @package Bolt\tests\protocol\v6
 */
class StructuresTest extends \Bolt\tests\structures\StructureLayer
{
    public function testInit(): AProtocol
    {
        $conn = new \Bolt\connection\StreamSocket($GLOBALS['NEO_HOST'] ?? '127.0.0.1', $GLOBALS['NEO_PORT'] ?? 7687);
        $this->assertInstanceOf(\Bolt\connection\StreamSocket::class, $conn);

        $bolt = new Bolt($conn);
        $this->assertInstanceOf(Bolt::class, $bolt);

        $protocol = $bolt->build();
        $this->assertInstanceOf(AProtocol::class, $protocol);

        if (version_compare($protocol->getVersion(), '6', '<')) {
            $this->markTestSkipped('Tests available only for version 6 and higher.');
        }

        $this->sayHello($protocol, $GLOBALS['NEO_USER'], $GLOBALS['NEO_PASS']);

        return $protocol;
    }

    /**
     * @depends testInit
     */
    public function testVector(AProtocol $protocol)
    {
        //unpack
        $res = iterator_to_array(
            $protocol
                ->run(
                    'CYPHER 25 RETURN vector([1.05, 0.123, 5], 3, FLOAT),
                    vector([1.05, 0.123, 5], 3, FLOAT32),
                    vector([5, 543, 342765], 3, INTEGER),
                    vector([5, -60, 120], 3, INTEGER8),
                    vector([5, -20000, 30000], 3, INTEGER16),
                    vector([5, -2000000000, 2000000000], 3, INTEGER32)',
                    [],
                    ['mode' => 'r']
                )
                ->pull()
                ->getResponses(),
            false
        );

        foreach ($res[1]->content as $vector) {
            $this->assertInstanceOf(Vector::class, $vector);
        }

        // float64
        $values = $res[1]->content[0]->decode();
        $this->assertEqualsWithDelta([1.05, 0.123, 5], $values, 1e-6);
        // float32
        $values = $res[1]->content[1]->decode();
        $this->assertEqualsWithDelta([1.05, 0.123, 5], $values, 1e-6);
        // int64
        $values = $res[1]->content[2]->decode();
        $this->assertEquals([5, 543, 342765], $values);
        // int8
        $values = $res[1]->content[3]->decode();
        $this->assertEquals([5, -60, 120], $values);
        // int16
        $values = $res[1]->content[4]->decode();
        $this->assertEquals([5, -20000, 30000], $values);
        // int32
        $values = $res[1]->content[5]->decode();
        $this->assertEquals([5, -2000000000, 2000000000], $values);

        //pack
        $res = iterator_to_array(
            $protocol
                ->run('CYPHER 25 RETURN toFloatList($float), toIntegerList($int64), toIntegerList($int8), toIntegerList($int16), toIntegerList($int32)', [
                    'float' => Vector::encode([1.05, 0.123, 5.0]),
                    'int64' => Vector::encode([5, -21474836480, 21474836470]),
                    'int8' => Vector::encode([5, -60, 120]),
                    'int16' => Vector::encode([5, -20000, 30000]),
                    'int32' => Vector::encode([5, -2000000000, 2000000000]),
                ], ['mode' => 'r'])
                ->pull()
                ->getResponses(),
            false
        );

        $this->assertEqualsWithDelta([1.05, 0.123, 5], $res[1]->content[0], 1e-6);
        $this->assertEquals([5, -21474836480, 21474836470], $res[1]->content[1]);
        $this->assertEquals([5, -60, 120], $res[1]->content[2]);
        $this->assertEquals([5, -20000, 30000], $res[1]->content[3]);
        $this->assertEquals([5, -2000000000, 2000000000], $res[1]->content[4]);
    }

    /**
     * @depends testInit
     */
    public function testVectorExceptions()
    {
        $this->expectException(\InvalidArgumentException::class);
        Vector::encode(['abc', 'def']);
    }
}
