<?php

namespace Bolt\tests\protocol;

use Bolt\protocol\V3;
use Bolt\enum\{Signature, ServerState};

/**
 * Class V3Test
 *
 * @author Michal Stefanak
 * @link https://github.com/stefanak-michal/php-bolt-driver
 * @package Bolt\tests\protocol
 */
class V3Test extends ProtocolLayer
{
    public function test__construct(): V3
    {
        $cls = new V3(1, $this->mockConnection());
        $this->assertInstanceOf(V3::class, $cls);
        return $cls;
    }

    /**
     * @depends test__construct
     */
    public function testHello(V3 $cls): void
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

    /**
     * @depends test__construct
     */
    public function testRun(V3 $cls): void
    {
        self::$readArray = [
            [0x70, (object)[]],
            [0x7F, (object)['message' => 'some error message', 'code' => 'Neo.ClientError.Statement.SyntaxError']],
            [0x7E, (object)[]]
        ];
        self::$writeBuffer = [
            '0001b3',
            '000110',
            '00098852455455524e2031',
            '0001a0',
            '0001a0',

            '00 01 b3',
            '00 01 10',
            '00 0a 89 6e    6f 74 20 61    20 43 51 4c',
            '00 01 a0',
            '00 01 a0',

            '00 01 b3',
            '00 01 10',
            '00 0a 89 6e    6f 74 20 61    20 43 51 4c',
            '00 01 a0',
            '00 01 a0',
        ];

        $cls->serverState = ServerState::READY;
        $this->assertEquals(Signature::SUCCESS, $cls->run('RETURN 1')->getResponse()->signature);
        $this->assertEquals(ServerState::STREAMING, $cls->serverState);

        $cls->serverState = ServerState::READY;
        $response = $cls->run('not a CQL')->getResponse();
        $this->checkFailure($response);
        $this->assertEquals(ServerState::FAILED, $cls->serverState);

        $cls->serverState = ServerState::INTERRUPTED;
        $response = $cls->run('not a CQL')->getResponse();
        $this->assertEquals(Signature::IGNORED, $response->signature);
        $this->assertEquals(ServerState::INTERRUPTED, $cls->serverState);
    }

    /**
     * @depends test__construct
     */
    public function testBegin(V3 $cls): void
    {
        self::$readArray = [
            [0x70, (object)[]],
            [0x7F, (object)['message' => 'some error message', 'code' => 'Neo.ClientError.Statement.SyntaxError']],
            [0x7E, (object)[]]
        ];
        self::$writeBuffer = [
            '00 01 b1',
            '00 01 11',
            '00 01 a0',
        ];

        $cls->serverState = ServerState::READY;
        $this->assertEquals(Signature::SUCCESS, $cls->begin()->getResponse()->signature);
        $this->assertEquals(ServerState::TX_READY, $cls->serverState);

        $cls->serverState = ServerState::READY;
        $response = $cls->begin()->getResponse();
        $this->checkFailure($response);
        $this->assertEquals(ServerState::FAILED, $cls->serverState);

        $cls->serverState = ServerState::INTERRUPTED;
        $response = $cls->begin()->getResponse();
        $this->assertEquals(Signature::IGNORED, $response->signature);
        $this->assertEquals(ServerState::INTERRUPTED, $cls->serverState);
    }

    /**
     * @depends test__construct
     */
    public function testCommit(V3 $cls): void
    {
        self::$readArray = [
            [0x70, (object)[]],
            [0x7F, (object)['message' => 'some error message', 'code' => 'Neo.ClientError.Statement.SyntaxError']],
            [0x7E, (object)[]]
        ];
        self::$writeBuffer = [
            '00 01 b0',
            '00 01 12',
        ];

        $cls->serverState = ServerState::TX_READY;
        $this->assertEquals(Signature::SUCCESS, $cls->commit()->getResponse()->signature);
        $this->assertEquals(ServerState::READY, $cls->serverState);

        $cls->serverState = ServerState::TX_READY;
        $response = $cls->commit()->getResponse();
        $this->checkFailure($response);
        $this->assertEquals(ServerState::FAILED, $cls->serverState);

        $cls->serverState = ServerState::INTERRUPTED;
        $response = $cls->commit()->getResponse();
        $this->assertEquals(Signature::IGNORED, $response->signature);
        $this->assertEquals(ServerState::INTERRUPTED, $cls->serverState);
    }

    /**
     * @depends test__construct
     */
    public function testRollback(V3 $cls): void
    {
        self::$readArray = [
            [0x70, (object)[]],
            [0x7F, (object)['message' => 'some error message', 'code' => 'Neo.ClientError.Statement.SyntaxError']],
            [0x7E, (object)[]]
        ];
        self::$writeBuffer = [
            '00 01 b0',
            '00 01 13',
        ];

        $cls->serverState = ServerState::TX_READY;
        $this->assertEquals(Signature::SUCCESS, $cls->rollback()->getResponse()->signature);
        $this->assertEquals(ServerState::READY, $cls->serverState);

        $cls->serverState = ServerState::TX_READY;
        $response = $cls->rollback()->getResponse();
        $this->checkFailure($response);
        $this->assertEquals(ServerState::FAILED, $cls->serverState);

        $cls->serverState = ServerState::INTERRUPTED;
        $response = $cls->rollback()->getResponse();
        $this->assertEquals(Signature::IGNORED, $response->signature);
        $this->assertEquals(ServerState::INTERRUPTED, $cls->serverState);
    }

    /**
     * @depends test__construct
     */
    public function testGoodbye(V3 $cls): void
    {
        self::$readArray = [1, 2, 0];
        self::$writeBuffer = [
            '0001b0',
            '000102',
        ];

        $cls->goodbye();
        $this->assertEquals(ServerState::DEFUNCT, $cls->serverState);
    }

}
