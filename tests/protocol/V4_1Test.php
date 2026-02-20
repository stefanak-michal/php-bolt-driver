<?php

namespace Bolt\tests\protocol;

use Bolt\protocol\V4_1;
use Bolt\enum\{Signature, ServerState};

/**
 * Class V4_1Test
 *
 * @author Michal Stefanak
 * @link https://github.com/stefanak-michal/php-bolt-driver
 * @package Bolt\tests\protocol
 */
class V4_1Test extends ProtocolLayer
{
    public function test__construct(): V4_1
    {
        $cls = new V4_1(1, $this->mockConnection());
        $this->assertInstanceOf(V4_1::class, $cls);
        return $cls;
    }

    /**
     * @depends test__construct
     */
    public function testHello(V4_1 $cls): void
    {
        self::$readArray = [
            [0x70, (object)[]],
            [0x7F, (object)['message' => 'some error message', 'code' => 'Neo.ClientError.Statement.SyntaxError']]
        ];
        self::$writeBuffer = [
            '0001b1',
            '000101',
            '0001a4',
            '000b8a757365725f6167656e74',
            '000988626f6c742d706870',
            '000786736368656d65',
            '0006856261736963',
            '000a897072696e636970616c',
            '00058475736572',
            '000c8b63726564656e7469616c73',
            '00098870617373776f7264',
        ];

        $cls->serverState = ServerState::CONNECTED;
        $this->assertEquals(Signature::SUCCESS, $cls->hello([
            'user_agent' => 'bolt-php',
            'scheme' => 'basic',
            'principal' => 'user',
            'credentials' => 'password',
        ])->getResponse()->signature);
        $this->assertEquals(ServerState::READY, $cls->serverState);

        $cls->serverState = ServerState::CONNECTED;
        $response = $cls->hello([
            'user_agent' => 'bolt-php',
            'scheme' => 'basic',
            'principal' => 'user',
            'credentials' => 'password',
        ])->getResponse();
        $this->checkFailure($response);
        $this->assertEquals(ServerState::DEFUNCT, $cls->serverState);
    }
}
