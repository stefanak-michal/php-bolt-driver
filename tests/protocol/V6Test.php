<?php

namespace Bolt\tests\protocol;

use Bolt\protocol\V6;

/**
 * Class V6Test
 *
 * @author Michal Stefanak
 * @link https://github.com/neo4j-php/Bolt
 * @package Bolt\tests\protocol
 */
class V6Test extends ProtocolLayer
{
    public function test__construct(): V6
    {
        $cls = new V6(1, $this->mockConnection());
        $this->assertInstanceOf(V6::class, $cls);
        return $cls;
    }
}
