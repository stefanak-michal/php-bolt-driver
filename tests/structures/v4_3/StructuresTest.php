<?php

namespace Bolt\tests\structures\v4_3;

use Bolt\Bolt;
use Bolt\protocol\{
    AProtocol,
    Response,
    V4_3,
    V4_4
};
use Bolt\protocol\v5\structures\{
    DateTime,
    DateTimeZoneId
};
use Bolt\enum\Signature;

/**
 * Class StructuresTest
*
* @author Michal Stefanak
* @link https://github.com/neo4j-php/Bolt
* @package Bolt\tests\protocol\v4_3
*/
class StructuresTest extends \Bolt\tests\structures\DateTimeUpdate
{
    protected string $expectedDateTimeClass = DateTime::class;
    protected string $expectedDateTimeZoneIdClass = DateTimeZoneId::class;

    public function testInit(): AProtocol|V4_4|V4_3
    {
        $conn = new \Bolt\connection\StreamSocket($GLOBALS['NEO_HOST'] ?? '127.0.0.1', $GLOBALS['NEO_PORT'] ?? 7687);
        $this->assertInstanceOf(\Bolt\connection\StreamSocket::class, $conn);

        $bolt = new Bolt($conn);
        $this->assertInstanceOf(Bolt::class, $bolt);

        $bolt->setProtocolVersions(4.4, 4.3);
        /** @var AProtocol|V4_4|V4_3 $protocol */
        $protocol = $bolt->build();
        $this->assertInstanceOf(AProtocol::class, $protocol);

        /** @var Response $helloResponse */
        $helloResponse = $protocol->hello([
            'user_agent' => 'bolt-php',
            'scheme' => 'basic',
            'principal' => $GLOBALS['NEO_USER'],
            'credentials' => $GLOBALS['NEO_PASS'],
            'patch_bolt' => ['utc']
        ])->getResponse();
        $this->assertEquals(Signature::SUCCESS, $helloResponse->signature);

        if (version_compare($protocol->getVersion(), '5', '>=') || version_compare($protocol->getVersion(), '4.3', '<')) {
            $this->markTestSkipped('You are not running Neo4j version with patch_bolt support.');
        }

        if (($helloResponse->content['patch_bolt'] ?? null) !== ['utc']) {
            $this->markTestSkipped('Currently used Neo4j version does not support patch_bolt.');
        }

        return $protocol;
    }

}
