<?php

namespace Bolt\tests\protocol;

use Bolt\protocol\V5_3;
use Bolt\enum\{Signature, ServerState};

/**
 * Class V5_3Test
 *
 * @author Michal Stefanak
 * @link https://github.com/stefanak-michal/php-bolt-driver
 * @package Bolt\tests\protocol
 */
class V5_3Test extends \Bolt\tests\protocol\ProtocolLayer
{
    public function test__construct(): V5_3
    {
        $cls = new V5_3(1, $this->mockConnection());
        $this->assertInstanceOf(V5_3::class, $cls);
        return $cls;
    }

    /**
     * @depends test__construct
     */
    public function testHello(V5_3 $cls): void
    {
        self::$readArray = [
            [0x70, (object)[]],
            [0x7F, (object)['message' => 'some error message', 'code' => 'Neo.ClientError.Statement.SyntaxError']]
        ];

        $cls->serverState = ServerState::NEGOTIATION;
        $this->assertEquals(Signature::SUCCESS, $cls->hello()->getResponse()->signature);
        $this->assertEquals(ServerState::AUTHENTICATION, $cls->serverState);

        $cls->serverState = ServerState::NEGOTIATION;
        $response = $cls->hello()->getResponse();
        $this->checkFailure($response);
        $this->assertEquals(ServerState::DEFUNCT, $cls->serverState);
    }
}
