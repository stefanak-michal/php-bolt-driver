<?php

namespace Bolt\tests\protocol;

use Bolt\protocol\V4_4;
use Bolt\enum\{Signature, ServerState};

/**
 * Class V4_4Test
 *
 * @author Michal Stefanak
 * @link https://github.com/stefanak-michal/php-bolt-driver
 * @package Bolt\tests\protocol
 */
class V4_4Test extends ProtocolLayer
{
    public function test__construct(): V4_4
    {
        $cls = new V4_4(1, $this->mockConnection());
        $this->assertInstanceOf(V4_4::class, $cls);
        return $cls;
    }

    /**
     * @depends test__construct
     */
    public function testRoute(V4_4 $cls): void
    {
        self::$readArray = [
            [0x70, (object)[]],
            [0x7F, (object)['message' => 'some error message', 'code' => 'Neo.ClientError.Statement.SyntaxError']],
            [0x7E, (object)[]]
        ];
        self::$writeBuffer = [
            '0001b3',
            '000166',
            '0001a1',
            '00088761646472657373',
            '000f8e6c6f63616c686f73743a37363837',
            '000190',
            '0001a1',
            '0003826462',
            '0001c0',
        ];

        $cls->serverState = ServerState::READY;
        $this->assertEquals(Signature::SUCCESS, $cls->route(['address' => 'localhost:7687'], [], ['db' => null])->getResponse()->signature);
        $this->assertEquals(ServerState::READY, $cls->serverState);

        $cls->serverState = ServerState::READY;
        $response = $cls->route(['address' => 'localhost:7687'], [], ['db' => null])->getResponse();
        $this->checkFailure($response);
        $this->assertEquals(ServerState::FAILED, $cls->serverState);

        $cls->serverState = ServerState::INTERRUPTED;
        $response = $cls->route(['address' => 'localhost:7687'], [], ['db' => null])->getResponse();
        $this->assertEquals(Signature::IGNORED, $response->signature);
        $this->assertEquals(ServerState::INTERRUPTED, $cls->serverState);
    }

}
