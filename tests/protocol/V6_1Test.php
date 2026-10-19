<?php

namespace Bolt\tests\protocol;

use Bolt\packstream\v1_1\Uuid;
use Bolt\protocol\V6_1;

/**
 * Class V6_1Test
 *
 * @author Michal Stefanak
 * @link https://github.com/stefanak-michal/php-bolt-driver
 * @package Bolt\tests\protocol
 */
class V6_1Test extends ProtocolLayer
{
    public function test__construct(): V6_1
    {
        $cls = new V6_1($this->mockConnection());
        $this->assertInstanceOf(V6_1::class, $cls);
        return $cls;
    }

    /**
     * @depends test__construct
     */
    public function testPackStream(V6_1 $protocol)
    {
        $method = new \ReflectionMethod($protocol, 'getPackStreamVersion');
        $this->assertEquals('1_1', $method->invoke($protocol));
    }

    /**
     * @depends test__construct
     */
    public function testUuid(V6_1 $protocol)
    {
        $uuid = new Uuid($this->generateUuidV4());

        $res = iterator_to_array(
            $protocol
                ->run('RETURN $uuid', ['uuid' => $uuid])
                ->pull()
                ->getResponses(),
            false
        );
        $this->assertEquals($uuid, $res[1]->content[0]);
    }

    private function generateUuidV4(): string
    {
        $bytes = random_bytes(16);

        // Set version to 4 (0100 in binary)
        $bytes[6] = chr(ord($bytes[6]) & 0x0F | 0x40);

        // Set variant to RFC 4122 (10xx in binary)
        $bytes[8] = chr(ord($bytes[8]) & 0x3F | 0x80);

        $hex = bin2hex($bytes);
        return substr($hex, 0, 8)
            . substr($hex, 8, 4)
            . substr($hex, 12, 4)
            . substr($hex, 16, 4)
            . substr($hex, 20, 12);
    }
}
