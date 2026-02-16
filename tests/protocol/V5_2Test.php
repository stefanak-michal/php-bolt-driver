<?php

use Bolt\protocol\V5_2;

/**
 * Class V5_2Test
 *
 * @author Michal Stefanak
 * @link https://github.com/stefanak-michal/php-bolt-driver
 * @package Bolt\tests\protocol
 */
class V5_2Test extends \Bolt\tests\protocol\ProtocolLayer
{
    public function test__construct(): V5_2
    {
        $cls = new V5_2(1, $this->mockConnection());
        $this->assertInstanceOf(V5_2::class, $cls);
        return $cls;
    }
}
