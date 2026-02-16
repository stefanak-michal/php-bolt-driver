<?php

namespace Bolt\tests\helpers;

use Bolt\helpers\Client;
use Exception;
use PHPUnit\Framework\TestCase;

/**
 * Class ClientTest
 *
 * @author Michal Stefanak
 * @link https://github.com/stefanak-michal/php-bolt-driver
 * @package Bolt\tests\helpers
 */
class ClientTest extends TestCase
{
    private function getTestSuite(): string {
        $argv = $_SERVER['argv'] ?? [];

        foreach ($argv as $index => $arg) {
            if ($arg === '--testsuite') {
                return $argv[$index + 1] ?? '';
            }
        }

        return '';
    }

    private function setUpClient(): void
    {
        $testsuite = $this->getTestSuite();

        $conn = new \Bolt\connection\Socket('127.0.0.1', 7687);
        $bolt = new \Bolt\Bolt($conn);
        $protocol = $bolt->build();

        Client::setProtocol($protocol, $testsuite === 'Neo4j' ? [
            'scheme' => 'basic',
            'principal' => $GLOBALS['NEO_USER'],
            'credentials' => $GLOBALS['NEO_PASS']
        ] : [
            'scheme' => 'none'
        ]);
    }

    public function testQuery(): void
    {
        Client::setLogHandler(null);
        Client::setErrorHandler(null);

        $this->setUpClient();

        $data = Client::query('RETURN 1 AS num, "Hello, World!" AS str');
        $this->assertEquals(1, $data[0]['num']);
        $this->assertEquals('Hello, World!', $data[0]['str']);

        $data = Client::queryFirstField('RETURN 1 AS num');
        $this->assertEquals(1, $data);
        
        $data = Client::queryFirstColumn('UNWIND [1, 2, 3] AS num RETURN num');
        $this->assertEquals([1, 2, 3], $data);
    }

    public function testErrorHandler(): void
    {
        $testsuite = self::getTestSuite();
        if ($testsuite !== 'Neo4j') {
            $this->markTestSkipped('This test is only executed with Neo4j, skipping.');
        }

        Client::setErrorHandler(function (Exception $exception) {
            throw $exception;
        });

        $conn = new \Bolt\connection\Socket('127.0.0.1', 7687);
        $bolt = new \Bolt\Bolt($conn);
        $protocol = $bolt->build();

        $this->expectException(Exception::class);

        Client::setProtocol($protocol);
    }

    public function testLogHandler(): void
    {
        Client::setLogHandler(null);
        Client::setErrorHandler(null);

        $this->setUpClient();

        Client::setLogHandler(function (string $message, array $data, array $extra) {
            $this->assertEquals('RETURN $num AS num, $str AS str', $message);
            $this->assertEquals(['num' => 1, 'str' => 'Hello, World!'], $data);
            $this->assertEquals(['rows' => 1], $extra);
        });

        $data = Client::query('RETURN $num AS num, $str AS str', ['num' => 1, 'str' => 'Hello, World!']);
        $this->assertEquals(1, $data[0]['num']);
        $this->assertEquals('Hello, World!', $data[0]['str']);
    }

    public function testTransaction(): void
    {
        Client::setLogHandler(null);
        Client::setErrorHandler(null);

        $this->setUpClient();

        Client::begin();
        Client::query('CREATE (n:Test {name: "Transaction Test"})');
        $data = Client::query('MATCH (n:Test {name: "Transaction Test"}) RETURN n');
        $this->assertEquals('Transaction Test', $data[0]['n']->properties['name']);
        Client::rollback();

        $data = Client::query('MATCH (n:Test {name: "Transaction Test"}) RETURN n');
        $this->assertEmpty($data);
    }
}
