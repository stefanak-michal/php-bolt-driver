<?php

use Bolt\protocol\V5_4;

/**
 * Class V5_4Test
 *
 * @author Michal Stefanak
 * @link https://github.com/stefanak-michal/php-bolt-driver
 * @package Bolt\tests\protocol
 */
class V5_4Test extends \Bolt\tests\protocol\ProtocolLayer
{
    public function test__construct(): V5_4
    {
        $cls = new V5_4(1, $this->mockConnection());
        $this->assertInstanceOf(V5_4::class, $cls);
        return $cls;
    }

    /**
     * @depends test__construct
     */
    public function testTelemetry(V5_4 $cls): void
    {
        // todo
        $this->markTestSkipped();
    }
}
