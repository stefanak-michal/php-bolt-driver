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

    // todo ..also test encode and decode in Vector class
    public function testVector(AProtocol $protocol)
    {
        //unpack
        // $res = iterator_to_array(
        //     $protocol
        //         ->run('RETURN ', [], ['mode' => 'r'])
        //         ->pull()
        //         ->getResponses(),
        //     false
        // );
        // $this->assertInstanceOf(Vector::class, $res[1]->content[0]);

        //pack
        // $res = iterator_to_array(
        //     $protocol
        //         ->run('RETURN toString($p)', [
        //             'p' => $res[1]->content[0]
        //         ], ['mode' => 'r'])
        //         ->pull()
        //         ->getResponses(),
        //     false
        // );
        // $this->assertStringStartsWith('point(', $res[1]->content[0]);
    }
}
