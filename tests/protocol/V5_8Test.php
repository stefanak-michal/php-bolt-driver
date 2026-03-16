<?php

use Bolt\protocol\V5_8;

/**
 * Class V5_8Test
 *
 * @author Michal Stefanak
 * @link https://github.com/stefanak-michal/php-bolt-driver
 * @package Bolt\tests\protocol
 */
class V5_8Test extends \Bolt\tests\protocol\ProtocolLayer
{
    public function test__construct(): V5_8
    {
        $cls = new V5_8(1, $this->mockConnection());
        $this->assertInstanceOf(V5_8::class, $cls);
        return $cls;
    }
}
