<?php

namespace Bolt\tests;

use Bolt\Bolt;
use Bolt\connection\StreamSocket;
use Bolt\helpers\Auth;
use Bolt\tests\PackStream\v1\generators\RandomDataGenerator;
use PHPUnit\Framework\TestCase;

class PerformanceTest extends TestCase
{
    public function test50KRecords(): void
    {
        $amount = 50000;

        $conn = new StreamSocket($GLOBALS['NEO_HOST'] ?? 'localhost', $GLOBALS['NEO_PORT'] ?? 7687);
        $protocol = (new Bolt($conn))->build();
        $this->assertNotEmpty($protocol->init(Auth::basic($GLOBALS['NEO_USER'], $GLOBALS['NEO_PASS'])));

        $generator = new RandomDataGenerator($amount);
        $protocol->run('UNWIND $x as x RETURN x', ['x' => $generator]);

        $count = 0;
        while ($count < $amount) {
            ++$count;
            $protocol->pull(['n' => 1]);
        }

        $this->assertEquals($amount, $count);
    }
}