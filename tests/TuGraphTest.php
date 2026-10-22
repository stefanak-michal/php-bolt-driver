<?php

namespace Bolt\tests;

use Bolt\Bolt;
use PHPUnit\Framework\TestCase;
use Bolt\helpers\Client;
use Bolt\connection\Socket;

/**
 * Class TuGraphTest
 * 
 * @author Michal Stefanak
 * @link https://github.com/stefanak-michal/php-bolt-driver
 * @package Bolt\tests
 */
class TuGraphTest extends TestCase
{
    public function testQuery(): void
    {
        $conn = new Socket('127.0.0.1', 7687);
        $bolt = new Bolt($conn);
        $bolt->setProtocolVersions(4.4);

        $client = new Client($bolt->build(), ['scheme' => 'basic', 'principal' => 'admin', 'credentials' => '73@TuGraph']);
        $extra = ['db' => 'default'];

        $data = $client->query('RETURN 1 AS num, "Hello, World!" AS str', [], $extra);
        $this->assertEquals(1, $data[0]['num']);
        $this->assertEquals('Hello, World!', $data[0]['str']);

        $data = $client->queryFirstField('RETURN 1 AS num', [], $extra);
        $this->assertEquals(1, $data);
        
        $data = $client->queryFirstColumn('UNWIND [1, 2, 3] AS num RETURN num', [], $extra);
        $this->assertEquals([1, 2, 3], $data);
    }
}
