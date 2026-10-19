<?php

namespace Bolt\tests\protocol;

use Bolt\packstream\v1_1\Uuid;
use Bolt\protocol\V6_1;

/**
 * Class V6_1Test
 *
 * @author Michal Stefanak
 * @link https://github.com/stefanak-michal/php-bolt-driver
 * @package Bolt\tests\protocol
 */
class V6_1Test extends ProtocolLayer
{
    public function test__construct(): V6_1
    {
        $cls = new V6_1($this->mockConnection());
        $this->assertInstanceOf(V6_1::class, $cls);
        return $cls;
    }

    /**
     * @depends test__construct
     */
    public function testPackStream(V6_1 $protocol)
    {
        $method = new \ReflectionMethod($protocol, 'getPackStreamVersion');
        $this->assertEquals('1_1', $method->invoke($protocol));
    }
}
