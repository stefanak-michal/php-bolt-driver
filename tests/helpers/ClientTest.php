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

    private function setUpClient($logHandler = null, $errorHandler = null): Client
    {
        $testsuite = $this->getTestSuite();

        $conn = new \Bolt\connection\Socket('127.0.0.1', 7687);
        $bolt = new \Bolt\Bolt($conn);
        return new Client($bolt->build(), $testsuite === 'Neo4j' ? [
            'scheme' => 'basic',
            'principal' => $GLOBALS['NEO_USER'],
            'credentials' => $GLOBALS['NEO_PASS']
        ] : [
            'scheme' => 'none'
        ], $logHandler, $errorHandler);

    }

    public function testQuery(): void
    {
        $client = $this->setUpClient();

        $data = $client->query('RETURN 1 AS num, "Hello, World!" AS str');
        $this->assertEquals(1, $data[0]['num']);
        $this->assertEquals('Hello, World!', $data[0]['str']);

        $data = $client->queryFirstField('RETURN 1 AS num');
        $this->assertEquals(1, $data);
        
        $data = $client->queryFirstColumn('UNWIND [1, 2, 3] AS num RETURN num');
        $this->assertEquals([1, 2, 3], $data);
    }

    public function testErrorHandler(): void
    {
        $testsuite = $this->getTestSuite();
        if ($testsuite !== 'Neo4j') {
            $this->markTestSkipped('This test is only executed with Neo4j, skipping.');
        }

        $conn = new \Bolt\connection\Socket('127.0.0.1', 7687);
        $bolt = new \Bolt\Bolt($conn);
        $this->expectException(Exception::class);
        $client = new Client($bolt->build(), [
            'scheme' => 'none'
        ], null, function (Exception $exception) {
            throw $exception;
        });
    }

    public function testLogHandler(): void
    {
        $client = $this->setUpClient(function (string $message, array $data, array $extra) {
            if ($message === 'RETURN $num AS num, $str AS str') {
                $this->assertEquals('RETURN $num AS num, $str AS str', $message);
                $this->assertEquals(['num' => 1, 'str' => 'Hello, World!'], $data);
                $this->assertEquals(['rows' => 1], $extra);
            }
        });

        $data = $client->query('RETURN $num AS num, $str AS str', ['num' => 1, 'str' => 'Hello, World!']);
        $this->assertEquals(1, $data[0]['num']);
        $this->assertEquals('Hello, World!', $data[0]['str']);
    }

    public function testTransaction(): void
    {
        $client = $this->setUpClient();

        $client->begin();
        $client->query('CREATE (n:Test {name: "Transaction Test"})');
        $data = $client->query('MATCH (n:Test {name: "Transaction Test"}) RETURN n');
        $this->assertEquals('Transaction Test', $data[0]['n']->properties['name']);
        $client->rollback();

        $data = $client->query('MATCH (n:Test {name: "Transaction Test"}) RETURN n');
        $this->assertEmpty($data);
    }

    public function testFailure(): void
    {
        $client = $this->setUpClient(null, function (Exception $exception) {
            $this->assertStringContainsString('SyntaxError', $exception->getMessage());
        });

        $client->query('This is not a query!');

        // After the failure, the client should still be usable
        $response = $client->query('RETURN 1 AS num');
        $this->assertEquals(1, $response[0]['num']);
    }
}
