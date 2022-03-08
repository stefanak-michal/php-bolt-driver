<?php

namespace Bolt\tests\PackStream\v1;

use Bolt\protocol\AProtocol;
use Bolt\Bolt;
use Exception;
use PHPUnit\Framework\TestCase;

/**
 * Class PackerTest
 *
 * @author Michal Stefanak
 * @link https://github.com/neo4j-php/Bolt
 *
 * @covers \Bolt\PackStream\v1\Packer
 *
 * @package Bolt\tests\PackStream\v1
 * @requires PHP >= 7.1
 * @requires extension mbstring
 * @requires extension json
 */
class PackerTest extends TestCase
{
    /**
     * @return AProtocol
     */
    public function testInit(): AProtocol
    {
        try {
            $conn = new \Bolt\connection\StreamSocket($GLOBALS['NEO_HOST'] ?? '127.0.0.1', $GLOBALS['NEO_PORT'] ?? 7687);
            $this->assertInstanceOf(\Bolt\connection\StreamSocket::class, $conn);

            $bolt = new Bolt($conn);
            $this->assertInstanceOf(Bolt::class, $bolt);

            $protocol = $bolt->build();
            $this->assertInstanceOf(AProtocol::class, $protocol);

            $this->assertNotEmpty($protocol->init(\Bolt\helpers\Auth::basic($GLOBALS['NEO_USER'], $GLOBALS['NEO_PASS'])));

            return $protocol;
        } catch (Exception $e) {
            $this->markTestIncomplete($e->getMessage());
        }
    }

    /**
     * @depends testInit
     * @param AProtocol $protocol
     */
    public function testNull(AProtocol $protocol)
    {
        $protocol->run('RETURN $n IS NULL', ['n' => null]);
        $res = $protocol->pullAll();
        $this->assertTrue($res[0][0]);
    }

    /**
     * @depends testInit
     * @param AProtocol $protocol
     */
    public function testBoolean(AProtocol $protocol)
    {
        $protocol->run('RETURN $b = true', ['b' => true]);
        $res = $protocol->pullAll();
        $this->assertTrue($res[0][0]);

        $protocol->run('RETURN $b = false', ['b' => false]);
        $res = $protocol->pullAll();
        $this->assertTrue($res[0][0]);
    }

    /**
     * @depends testInit
     * @param AProtocol $protocol
     */
    public function testInteger(AProtocol $protocol)
    {
        $arr = array_merge(
            range(-16, 127),
            [-17, -128, 128, 32767, 32768, 2147483647, 2147483648, 9223372036854775807, -129, -32768, -32769, -2147483648, -2147483649, -9223372036854775808]
        );
        $protocol->run('RETURN ' . implode(', ', array_map(function (int $key, int $value) {
                return '$' . $key . ' = ' . $value;
            }, array_keys($arr), $arr)), $arr);
        $res = $protocol->pullAll();

        foreach ($arr as $i => $_) {
            $this->assertTrue($res[0][$i]);
        }
    }

    /**
     * @depends testInit
     * @param AProtocol $protocol
     */
    public function testFloat(AProtocol $protocol)
    {
        for ($i = 0; $i < 10; $i++) {
            $num = mt_rand(-mt_getrandmax(), mt_getrandmax()) / mt_getrandmax();
            $protocol->run('RETURN ' . $num . ' + 0.000001 > $n > ' . $num . ' - 0.000001', ['n' => $num]); //epsilon comparison
            $res = $protocol->pullAll();
            $this->assertTrue($res[0][0]);
        }
    }

    /**
     * @depends testInit
     * @param AProtocol $protocol
     */
    public function testString(AProtocol $protocol)
    {
        $randomString = function (int $length) {
            $str = '';
            while (strlen($str) < $length)
                $str .= chr(mt_rand(32, 126));
            return $str;
        };

        foreach ([0, 10, 200, 60000, 200000] as $length) {
            $str = $randomString($length);
            $protocol->run('RETURN $s = "' . str_replace(['\\', '"'], ['\\\\', '\\"'], $str) . '"', ['s' => $str]);
            $res = $protocol->pullAll();
            $this->assertTrue($res[0][0]);
        }
    }

    /**
     * @depends testInit
     * @param AProtocol $protocol
     */
    public function testList(AProtocol $protocol)
    {
        foreach ([0, 10, 200, 60000, 200000] as $size) {
            $arr = $this->randomArray($size);
            $protocol->run('RETURN size($arr) = ' . count($arr), ['arr' => $arr]);
            $res = $protocol->pullAll();
            $this->assertTrue($res[0][0]);
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
     * @param AProtocol $protocol
     */
    public function testDictionary(AProtocol $protocol)
    {
        foreach ([0, 10, 200, 60000, 200000] as $size) {
            $arr = $this->randomArray($size);
            $protocol->run('RETURN size(keys($arr)) = ' . count($arr), ['arr' => (object)$arr]);
            $res = $protocol->pullAll();
            $this->assertTrue($res[0][0]);
        }
    }

}
