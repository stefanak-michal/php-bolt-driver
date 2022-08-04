<?php

namespace Bolt\tests\protocol;

use Bolt\protocol\ServerState;
use Bolt\protocol\V4_1;
use Exception;

/**
 * Class V4_1Test
 *
 * @author Michal Stefanak
 * @link https://github.com/neo4j-php/Bolt
 *
 * @covers \Bolt\protocol\AProtocol
 * @covers \Bolt\protocol\V4_1
 *
 * @package Bolt\tests\protocol
 */
class V4_1Test extends ATest
{
    /**
     * @return V4_1
     */
    public function test__construct(): V4_1
    {
        $cls = new V4_1(new \Bolt\PackStream\v1\Packer, new \Bolt\PackStream\v1\Unpacker, $this->mockConnection(), new \Bolt\protocol\ServerState());
        $this->assertInstanceOf(V4_1::class, $cls);
        $cls->serverState->expectedServerStateMismatchCallback = function (string $current, array $expected) {
            $this->markTestIncomplete('Server in ' . $current . ' state. Expected ' . implode(' or ', $expected) . '.');
        };
        return $cls;
    }

    /**
     * @depends test__construct
     * @param V4_1 $cls
     */
    public function testHello(V4_1 $cls)
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

        try {
            $cls->serverState->set(ServerState::CONNECTED);
            $this->assertIsArray($cls->hello(\Bolt\helpers\Auth::basic('user', 'password'), []));
            $this->assertEquals(ServerState::READY, $cls->serverState->get());
        } catch (Exception $e) {
            $this->markTestIncomplete($e->getMessage());
        }

        try {
            $cls->serverState->set(ServerState::CONNECTED);
            $cls->hello(\Bolt\helpers\Auth::basic('user', 'password'));
        } catch (Exception $e) {
            $this->assertEquals('some error message (Neo.ClientError.Statement.SyntaxError)', $e->getMessage());
            $this->assertEquals(ServerState::DEFUNCT, $cls->serverState->get());
        }
    }
}
