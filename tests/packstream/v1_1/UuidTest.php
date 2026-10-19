<?php

namespace Bolt\tests\packstream\v1_1;

use Bolt\Bolt;
use Bolt\packstream\v1_1\Uuid;
use Bolt\protocol\AProtocol;
use Bolt\protocol\V6_1;
use Bolt\tests\TestLayer;

class UuidTest extends TestLayer
{
    public function testInit(): AProtocol
    {
        $conn = new \Bolt\connection\StreamSocket($_ENV['GDB_HOST'], $_ENV['GDB_PORT']);
        $this->assertInstanceOf(\Bolt\connection\StreamSocket::class, $conn);

        $bolt = new Bolt($conn);
        $this->assertInstanceOf(Bolt::class, $bolt);

        $protocol = $bolt->setProtocolVersions($this->getCompatibleBoltVersion())->build();
        $this->assertInstanceOf(AProtocol::class, $protocol);

        $this->sayHello($protocol, $_ENV['GDB_USERNAME'], $_ENV['GDB_PASSWORD']);
        if (version_compare($protocol->getVersion(), '6.1', '<')) {
            $this->markTestSkipped('UUID is available only from Bolt 6.1');
        }

        return $protocol;
    }

    /**
     * @depends testInit
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
