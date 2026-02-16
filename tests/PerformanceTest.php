<?php

namespace Bolt\tests;

use Bolt\Bolt;
use Bolt\connection\Socket;
use Bolt\enum\Signature;
use Bolt\protocol\Response;
use Bolt\tests\packstream\v1\generators\RandomDataGenerator;

/**
 * Class PerformanceTest
 * @author Ghlen Nagels
 * @link https://github.com/stefanak-michal/php-bolt-driver
 * @package Bolt\tests
 */
class PerformanceTest extends TestLayer
{
    public function test50KRecords(): void
    {
        $amount = 50000;

        $conn = new Socket($GLOBALS['NEO_HOST'] ?? 'localhost', $GLOBALS['NEO_PORT'] ?? 7687, 120);
        $protocol = (new Bolt($conn))->setProtocolVersions($this->getCompatibleBoltVersion())->build();

        $this->sayHello($protocol, $GLOBALS['NEO_USER'], $GLOBALS['NEO_PASS']);

        //prevent multiple runs at once
        while (true) {
            $protocol->run('MATCH (n:Test50k) RETURN count(n)')->getResponse();
            /** @var Response $response */
            $response = $protocol->pull()->getResponse();
            if ($response->signature !== Signature::RECORD)
                $this->markTestSkipped('Response not as expected.');
            $protocol->getResponse();
            if ($response->content[0] > 0) {
                $this->markTestSkipped('Test is already running by another process.');
                return;
            } else {
                iterator_to_array($protocol->run('CREATE (n:Test50k)')->pull()->getResponses(), false);
                break;
            }
        }

        $generator = new RandomDataGenerator($amount);
        /** @var Response $response */
        $response = $protocol
            ->run('UNWIND $x as x RETURN x', ['x' => $generator])
            ->getResponse();

        if ($response->signature !== Signature::SUCCESS)
            $this->markTestIncomplete('[' . $response->content['code'] . '] ' . $response->content['message']);

        $count = 0;
        /** @var Response $response */
        foreach ($protocol->pull()->getResponses() as $response) {
            if ($response->signature === Signature::RECORD)
                $count++;
        }

        $protocol->run('MATCH (n:Test50k) DELETE n')->getResponses();
        $this->assertEquals($amount, $count);
    }
}
