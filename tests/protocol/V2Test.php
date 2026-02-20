<?php

namespace Bolt\tests\protocol;

use Bolt\protocol\V2;
use Bolt\packstream\v1\{Packer, Unpacker};

/**
 * Class V2Test
 *
 * @author Michal Stefanak
 * @link https://github.com/stefanak-michal/php-bolt-driver
 * @package Bolt\tests\protocol
 */
class V2Test extends ProtocolLayer
{
    public function test__construct(): V2
    {
        $cls = new V2(1, $this->mockConnection());
        $this->assertInstanceOf(V2::class, $cls);
        return $cls;
    }
}
