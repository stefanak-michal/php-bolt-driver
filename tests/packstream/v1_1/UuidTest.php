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
        $uuid = new Uuid('4288f64c-fcfa-432d-8535-c0d3a32b7d04');

        $res = iterator_to_array(
            $protocol
                ->run('RETURN $uuid', ['uuid' => $uuid])
                ->pull()
                ->getResponses(),
            false
        );
        $this->assertEquals($uuid, $res[1]->content[0]);
    }

    public function testInvalidUuid()
    {
        $this->expectException(\InvalidArgumentException::class);
        new Uuid('invalid');
    }
}
