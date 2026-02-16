<?php

use Bolt\protocol\V5_6;

/**
 * Class V5_6Test
 *
 * @author Michal Stefanak
 * @link https://github.com/stefanak-michal/php-bolt-driver
 * @package Bolt\tests\protocol
 */
class V5_6Test extends \Bolt\tests\protocol\ProtocolLayer
{
    public function test__construct(): V5_6
    {
        $cls = new V5_6(1, $this->mockConnection());
        $this->assertInstanceOf(V5_6::class, $cls);
        return $cls;
    }
}
