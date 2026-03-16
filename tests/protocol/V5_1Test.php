<?php

namespace Bolt\tests\protocol;

use Bolt\protocol\V5_1;
use Bolt\enum\{Signature, ServerState};

/**
 * Class V5_1Test
 *
 * @author Michal Stefanak
 * @link https://github.com/stefanak-michal/php-bolt-driver
 * @package Bolt\tests\protocol
 */
class V5_1Test extends \Bolt\tests\protocol\ProtocolLayer
{
    public function test__construct(): V5_1
    {
        $cls = new V5_1(1, $this->mockConnection());
        $this->assertInstanceOf(V5_1::class, $cls);
        return $cls;
    }

    /**
     * @depends test__construct
     */
    public function testHello(V5_1 $cls): void
    {
        self::$readArray = [
            [0x70, (object)[]],
            [0x7F, (object)['message' => 'some error message', 'code' => 'Neo.ClientError.Statement.SyntaxError']]
        ];
        self::$writeBuffer = [
            '0001b1',
            '000101',
            '0001a1',
            '000b8a757365725f6167656e74',
            '000988626f6c742d706870',
        ];

        $cls->serverState = ServerState::NEGOTIATION;
        $this->assertEquals(Signature::SUCCESS, $cls->hello()->getResponse()->signature);
        $this->assertEquals(ServerState::AUTHENTICATION, $cls->serverState);

        $cls->serverState = ServerState::NEGOTIATION;
        $response = $cls->hello()->getResponse();
        $this->checkFailure($response);
        $this->assertEquals(ServerState::DEFUNCT, $cls->serverState);
    }

    /**
     * @depends test__construct
     */
    public function testLogon(V5_1 $cls): void
    {
        self::$readArray = [
            [0x70, (object)[]],
            [0x7F, (object)['message' => 'some error message', 'code' => 'Neo.ClientError.Statement.SyntaxError']]
        ];
        self::$writeBuffer = [
            '0001b1',
            '00016a',
            '0001a3',
            '000786736368656d65',
            '0006856261736963',
            '000a897072696e636970616c',
            '00058475736572',
            '000c8b63726564656e7469616c73',
            '00098870617373776f7264',
        ];

        $cls->serverState = ServerState::AUTHENTICATION;
        $this->assertEquals(Signature::SUCCESS, $cls->logon([
            'scheme' => 'basic',
            'principal' => 'user',
            'credentials' => 'password'
        ])->getResponse()->signature);
        $this->assertEquals(ServerState::READY, $cls->serverState);

        $cls->serverState = ServerState::AUTHENTICATION;
        $response = $cls->logon([
            'scheme' => 'basic',
            'principal' => 'user',
            'credentials' => 'password'
        ])->getResponse();
        $this->checkFailure($response);
        $this->assertEquals(ServerState::DEFUNCT, $cls->serverState);
    }

    /**
     * @depends test__construct
     */
    public function testLogoff(V5_1 $cls): void
    {
        self::$readArray = [
            [0x70, (object)[]],
            [0x7F, (object)['message' => 'some error message', 'code' => 'Neo.ClientError.Statement.SyntaxError']]
        ];
        self::$writeBuffer = [
            '0001b0',
            '00016b',
        ];

        $cls->serverState = ServerState::READY;
        $this->assertEquals(Signature::SUCCESS, $cls->logoff()->getResponse()->signature);
        $this->assertEquals(ServerState::AUTHENTICATION, $cls->serverState);

        $cls->serverState = ServerState::READY;
        $response = $cls->logoff()->getResponse();
        $this->checkFailure($response);
        $this->assertEquals(ServerState::FAILED, $cls->serverState);
    }
}
