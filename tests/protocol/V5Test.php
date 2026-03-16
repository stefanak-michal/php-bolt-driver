<?php

namespace Bolt\tests\protocol;

use Bolt\protocol\V5;

/**
 * Class V5Test
 *
 * @author Michal Stefanak
 * @link https://github.com/stefanak-michal/php-bolt-driver
 * @package Bolt\tests\protocol
 */
class V5Test extends ProtocolLayer
{
    public function test__construct(): V5
    {
        $cls = new V5(1, $this->mockConnection());
        $this->assertInstanceOf(V5::class, $cls);
        return $cls;
    }
}
