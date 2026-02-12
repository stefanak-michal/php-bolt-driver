<?php

namespace Bolt\tests\packstream\v1;

use Bolt\Bolt;
use Bolt\protocol\{AProtocol, Response};
use Bolt\tests\TestLayer;
use Bolt\enum\Signature;

/**
 * Class UnpackerTest
 *
 * @author Michal Stefanak
 * @link https://github.com/neo4j-php/Bolt
 * @package Bolt\tests\packstream\v1
 */
class UnpackerTest extends TestLayer
{
    public function testInit(): AProtocol
    {
        $conn = new \Bolt\connection\StreamSocket($GLOBALS['NEO_HOST'], $GLOBALS['NEO_PORT']);
        $this->assertInstanceOf(\Bolt\connection\StreamSocket::class, $conn);

        $bolt = new Bolt($conn);
        $this->assertInstanceOf(Bolt::class, $bolt);

        $protocol = $bolt->setProtocolVersions($this->getCompatibleBoltVersion())->build();
        $this->assertInstanceOf(AProtocol::class, $protocol);

        $this->sayHello($protocol, $GLOBALS['NEO_USER'], $GLOBALS['NEO_PASS']);

        $conn->setTimeout(60 * 10);
        return $protocol;
    }

    /**
     * @depends testInit
     */
    public function testNull(AProtocol $protocol): void
    {
        $gen = $protocol
            ->run('RETURN null', [], ['mode' => 'r'])
            ->pull()
            ->getResponses();

        /** @var Response $response */
        foreach ($gen as $response) {
            if ($response->signature == Signature::RECORD)
                $this->assertNull($response->content[0]);
        }
    }

    /**
     * @depends testInit
     */
    public function testBoolean(AProtocol $protocol): void
    {
        $gen = $protocol
            ->run('RETURN true, false', [], ['mode' => 'r'])
            ->pull()
            ->getResponses();

        /** @var Response $response */
        foreach ($gen as $response) {
            if ($response->signature == Signature::RECORD) {
                $this->assertTrue($response->content[0]);
                $this->assertFalse($response->content[1]);
            }
        }
    }

    /**
     * @depends testInit
     */
    public function testInteger(AProtocol $protocol): void
    {
        $gen = $protocol
            ->run('RETURN -16, 0, 127, -17, -128, 128, 32767, 32768, 2147483647, 2147483648, 9223372036854775807, -129, -32768, -32769, -2147483648, -2147483649, -9223372036854775808', [], ['mode' => 'r'])
            ->pull()
            ->getResponses();

        /** @var Response $response */
        foreach ($gen as $response) {
            if ($response->signature == Signature::RECORD) {
                foreach ([-16, 0, 127, -17, -128, 128, 32767, 32768, 2147483647, 2147483648, 9223372036854775807, -129, -32768, -32769, -2147483648, -2147483649, -9223372036854775808] as $i => $value) {
                    $this->assertEquals($value, $response->content[$i]);
                }
            }
        }
    }

    /**
     * @depends testInit
     */
    public function testFloat(AProtocol $protocol): void
    {
        for ($i = 0; $i < 10; $i++) {
            $num = mt_rand(-mt_getrandmax(), mt_getrandmax()) / mt_getrandmax();

            $gen = $protocol
                ->run('RETURN ' . $num, [], ['mode' => 'r'])
                ->pull()
                ->getResponses();

            /** @var Response $response */
            foreach ($gen as $response) {
                if ($response->signature == Signature::RECORD) {
                    $this->assertEqualsWithDelta($num, $response->content[0], 0.000001);
                }
            }
        }
    }

    /**
     * @depends      testInit
     * @dataProvider stringProvider
     */
    public function testString(string $str, AProtocol $protocol): void
    {
        $gen = $protocol
            ->run('RETURN "' . str_replace(['\\', '"'], ['\\\\', '\\"'], $str) . '" AS a', [], ['mode' => 'r'])
            ->pull()
            ->getResponses();

        /** @var Response $response */
        foreach ($gen as $response) {
            if ($response->signature == Signature::RECORD) {
                $this->assertEquals($str, $response->content[0]);
            }
        }
    }

    public function stringProvider(): \Generator
    {
        foreach ([0, 10, 200, 60000, 200000] as $length)
            yield 'string length: ' . $length => [$this->randomString($length)];
    }

    private function randomString(int $length): string
    {
        $str = '';
        while (strlen($str) < $length)
            $str .= chr(mt_rand(32, 126));
        return $str;
    }

    /**
     * @depends      testInit
     * @dataProvider listProvider
     */
    public function testList(int $size, AProtocol $protocol): void
    {
        $gen = $protocol
            ->run('RETURN range(0, ' . $size . ') AS a', [], ['mode' => 'r'])
            ->pull()
            ->getResponses();

        /** @var Response $response */
        foreach ($gen as $response) {
            if ($response->signature == Signature::RECORD) {
                $this->assertEquals(range(0, $size), $response->content[0]);
            }
        }
    }

    public function listProvider(): \Generator
    {
        foreach ([0, 10, 200, 60000, 200000] as $size)
            yield 'list size: ' . $size => [$size];
    }

    /**
     * @depends      testInit
     * @dataProvider dictionaryProvider
     */
    public function testDictionary(string $query, int $size, AProtocol $protocol): void
    {
        $gen = $protocol
            ->run($query, [], ['mode' => 'r'])
            ->pull()
            ->getResponses();

        /** @var Response $response */
        foreach ($gen as $response) {
            if ($response->signature == Signature::RECORD) {
                $this->assertCount($size, $response->content[0]);
            } elseif ($response->signature == Signature::FAILURE) {
                $this->markTestIncomplete(print_r($response->content, true));
            }
        }
    }

    public function dictionaryProvider(): \Generator
    {
        foreach ([0, 10, 200, 20000, 70000] as $size) {
            yield 'dictionary size: ' . $size => ['RETURN apoc.map.fromLists(toStringList(range(1, ' . $size . ')), range(1, ' . $size . '))', $size];
        }
    }

}
