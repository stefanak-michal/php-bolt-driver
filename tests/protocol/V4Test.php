<?php

namespace Bolt\tests\protocol;

use Bolt\protocol\V4;
use Bolt\enum\{Signature, ServerState};

/**
 * Class V4Test
 *
 * @author Michal Stefanak
 * @link https://github.com/stefanak-michal/php-bolt-driver
 * @package Bolt\tests\protocol
 */
class V4Test extends ProtocolLayer
{
    public function test__construct(): V4
    {
        $cls = new V4(1, $this->mockConnection());
        $this->assertInstanceOf(V4::class, $cls);
        return $cls;
    }

    /**
     * @depends test__construct
     */
    public function testPull(V4 $cls): void
    {
        self::$readArray = [
            [0x71, (object)[]],
            [0x70, (object)[]],
            [0x7F, (object)['message' => 'some error message', 'code' => 'Neo.ClientError.Statement.SyntaxError']],
            [0x7E, (object)[]]
        ];
        self::$writeBuffer = [
            '00 01 b1',
            '00 01 3f',
            '00 01 a2',
            '00 02 81 6e',
            '00 01 ff',
            '00 04 83 71    69 64',
            '00 01 ff',
        ];

        $cls->serverState = ServerState::STREAMING;
        $res = iterator_to_array($cls->pull(['n' => -1, 'qid' => -1])->getResponses(), false);
        $this->assertIsArray($res);
        $this->assertCount(2, $res);
        $this->assertEquals(ServerState::READY, $cls->serverState);

        $cls->serverState = ServerState::STREAMING;
        $responses = iterator_to_array($cls->pull(['n' => -1, 'qid' => -1])->getResponses(), false);
        $this->checkFailure($responses[0]);
        $this->assertEquals(ServerState::FAILED, $cls->serverState);

        $cls->serverState = ServerState::INTERRUPTED;
        $responses = iterator_to_array($cls->pull(['n' => -1, 'qid' => -1])->getResponses(), false);
        $this->assertEquals(Signature::IGNORED, $responses[0]->signature);
        $this->assertEquals(ServerState::INTERRUPTED, $cls->serverState);
    }

    /**
     * @depends test__construct
     */
    public function testDiscard(V4 $cls): void
    {
        self::$readArray = [
            [0x70, (object)[]],
            [0x7F, (object)['message' => 'some error message', 'code' => 'Neo.ClientError.Statement.SyntaxError']],
            [0x7E, (object)[]]
        ];
        self::$writeBuffer = [
            '0001b1',
            '00012f',
            '0001a2',
            '0002816e',
            '0001ff',
            '000483716964',
            '0001ff',
        ];

        $cls->serverState = ServerState::STREAMING;
        $this->assertEquals(Signature::SUCCESS, $cls->discard(['n' => -1, 'qid' => -1])->getResponse()->signature);
        $this->assertEquals(ServerState::READY, $cls->serverState);

        $cls->serverState = ServerState::STREAMING;
        $response = $cls->discard(['n' => -1, 'qid' => -1])->getResponse();
        $this->checkFailure($response);
        $this->assertEquals(ServerState::FAILED, $cls->serverState);

        $cls->serverState = ServerState::INTERRUPTED;
        $response = $cls->discard(['n' => -1, 'qid' => -1])->getResponse();
        $this->assertEquals(Signature::IGNORED, $response->signature);
        $this->assertEquals(ServerState::INTERRUPTED, $cls->serverState);
    }
}
