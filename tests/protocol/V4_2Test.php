<?php

namespace Bolt\tests\protocol;

use Bolt\protocol\V4_2;

/**
 * Class V4_2Test
 *
 * @author Michal Stefanak
 * @link https://github.com/stefanak-michal/php-bolt-driver
 * @package Bolt\tests\protocol
 */
class V4_2Test extends ProtocolLayer
{
    public function test__construct(): V4_2
    {
        $cls = new V4_2(1, $this->mockConnection());
        $this->assertInstanceOf(V4_2::class, $cls);
        return $cls;
    }

}
