<?php

namespace Bolt\tests\packstream\v1;

use Bolt\Bolt;
use Bolt\protocol\AProtocol;
use Bolt\tests\TestLayer;

/**
 * Class PackerTest
 *
 * @author Michal Stefanak
 * @link https://github.com/neo4j-php/Bolt
 * @package Bolt\tests\packstream\v1
 */
class PackerTest extends TestLayer
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

        return $protocol;
    }

    /**
     * @depends testInit
     */
    public function testNull(AProtocol $protocol): void
    {
        $res = iterator_to_array(
            $protocol
                ->run('RETURN $n IS NULL', ['n' => null], ['mode' => 'r'])
                ->pull()
                ->getResponses(),
            false
        );
        $this->assertTrue($res[1]->content[0]);
    }

    /**
     * @depends testInit
     */
    public function testBoolean(AProtocol $protocol): void
    {
        $res = iterator_to_array(
            $protocol
                ->run('RETURN $b = true', ['b' => true], ['mode' => 'r'])
                ->pull()
                ->getResponses(),
            false
        );
        $this->assertTrue($res[1]->content[0]);

        $res = iterator_to_array(
            $protocol
                ->run('RETURN $b = false', ['b' => false], ['mode' => 'r'])
                ->pull()
                ->getResponses(),
            false
        );
        $this->assertTrue($res[1]->content[0]);
    }

    /**
     * @depends      testInit
     * @dataProvider providerInteger
     */
    public function testInteger(int $i, AProtocol $protocol): void
    {
        $res = iterator_to_array(
            $protocol
                ->run('RETURN $i = ' . $i, ['i' => $i], ['mode' => 'r'])
                ->pull()
                ->getResponses(),
            false
        );
        $this->assertTrue($res[1]->content[0]);
    }

    public function providerInteger(): \Generator
    {
        foreach (range(-16, 127) as $i)
            yield 'int ' . $i => [$i];
        foreach ([-17, -128, 128, 32767, 32768, 2147483647, 2147483648, 9223372036854775807, -129, -32768, -32769, -2147483648, -2147483649, -9223372036854775808] as $i)
            yield 'int ' . number_format($i, 0, '', ' ') => [(int)$i];
    }

    /**
     * @depends testInit
     */
    public function testFloat(AProtocol $protocol): void
    {
        for ($i = 0; $i < 10; $i++) {
            $num = mt_rand(-mt_getrandmax(), mt_getrandmax()) / mt_getrandmax();
            $res = iterator_to_array(
                $protocol
                    ->run('RETURN ' . $num . ' + 0.000001 > $n > ' . $num . ' - 0.000001', ['n' => $num], ['mode' => 'r'])
                    ->pull()
                    ->getResponses(),
                false
            );
            $this->assertTrue($res[1]->content[0]);
        }
    }

    /**
     * @depends testInit
     */
    public function testString(AProtocol $protocol): void
    {
        $randomString = function (int $length) {
            $str = '';
            while (strlen($str) < $length)
                $str .= chr(mt_rand(32, 126));
            return $str;
        };

        foreach ([0, 10, 200, 60000, 200000] as $length) {
            $str = $randomString($length);
            $res = iterator_to_array(
                $protocol
                    ->run('RETURN $s = "' . str_replace(['\\', '"'], ['\\\\', '\\"'], $str) . '"', ['s' => $str], ['mode' => 'r'])
                    ->pull()
                    ->getResponses(),
                false
            );
            $this->assertTrue($res[1]->content[0]);
        }
    }

    /**
     * @depends testInit
     */
    public function testList(AProtocol $protocol): void
    {
        foreach ([0, 10, 200, 60000, 200000] as $size) {
            $arr = $this->randomArray($size);
            $res = iterator_to_array(
                $protocol
                    ->run('RETURN size($arr) = ' . count($arr), ['arr' => $arr], ['mode' => 'r'])
                    ->pull()
                    ->getResponses(),
                false
            );
            $this->assertTrue($res[1]->content[0]);
        }
    }

    private function randomArray(int $size): array
    {
        $arr = [];
        while (count($arr) < $size) {
            $arr[] = mt_rand(-1000, 1000);
        }
        return $arr;
    }

    /**
     * @depends testInit
     */
    public function testDictionary(AProtocol $protocol): void
    {
        foreach ([0, 10, 200, 60000, 200000] as $size) {
            $arr = $this->randomArray($size);
            $res = iterator_to_array(
                $protocol
                    ->run('RETURN size(keys($arr)) = ' . count($arr), ['arr' => (object)$arr], ['mode' => 'r'])
                    ->pull()
                    ->getResponses(),
                false
            );
            $this->assertTrue($res[1]->content[0]);
        }
    }

    /**
     * @depends testInit
     */
    public function testListGenerator(AProtocol $protocol): void
    {
        $data = [
            'first',
            'second',
            'third'
        ];
        $list = new \Bolt\tests\packstream\v1\generators\ListGenerator($data);
        $result = iterator_to_array(
            $protocol
                ->run('UNWIND $list AS row RETURN row', ['list' => $list])
                ->pull()
                ->getResponses(),
            false
        );
        foreach ($data as $i => $value)
            $this->assertEquals($value, $result[1 + $i]->content[0]);
    }

    /**
     * @depends testInit
     */
    public function testDictionaryGenerator(AProtocol $protocol): void
    {
        $data = [
            'a' => 'first',
            'b' => 'second',
            'c' => 'third'
        ];
        $dict = new \Bolt\tests\packstream\v1\generators\DictionaryGenerator($data);
        $result = iterator_to_array(
            $protocol
                ->run('RETURN $dict', ['dict' => $dict])
                ->pull()
                ->getResponses(),
            false
        );
        $this->assertEquals($data, $result[1]->content[0]);
    }

}
