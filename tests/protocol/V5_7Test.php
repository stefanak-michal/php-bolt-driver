<?php

use Bolt\protocol\V5_7;

/**
 * Class V5_7Test
 *
 * @author Michal Stefanak
 * @link https://github.com/stefanak-michal/php-bolt-driver
 * @package Bolt\tests\protocol
 */
class V5_7Test extends \Bolt\tests\protocol\ProtocolLayer
{
    public function test__construct(): V5_7
    {
        $cls = new V5_7(1, $this->mockConnection());
        $this->assertInstanceOf(V5_7::class, $cls);
        return $cls;
    }
}
