<?php

namespace Bolt\tests\packstream\v1_1;

use Bolt\packstream\v1_1\Packer;
use Bolt\packstream\v1_1\Unpacker;
use Bolt\packstream\v1_1\Uuid;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * Offline unit tests for PackStream 1.1 UUID unpacking.
 *
 * @link https://neo4j.com/docs/bolt/current/packstream/#data-type-uuid
 */
class UnpackerTest extends TestCase
{
    public function testUnpackUuidFromPackStreamBytes(): void
    {
        // Spec example: 550e8400-e29b-41d4-a716-446655440000
        $payload = chr(0xE0) . hex2bin('550e8400e29b41d4a716446655440000');

        $uuid = (new Unpacker())->unpack($payload);

        $this->assertInstanceOf(Uuid::class, $uuid);
        $this->assertSame('550e8400-e29b-41d4-a716-446655440000', $uuid->value);
    }

    public function testPackUnpackUuidRoundTrip(): void
    {
        $expected = new Uuid('4288f64c-fcfa-432d-8535-c0d3a32b7d04');

        $packValue = new ReflectionMethod(Packer::class, 'packValue');
        $packValue->setAccessible(true);

        $packed = '';
        foreach ($packValue->invoke(new Packer(), $expected) as $chunk) {
            $packed .= $chunk;
        }

        $this->assertSame(
            chr(0xE0) . hex2bin('4288f64cfcfa432d8535c0d3a32b7d04'),
            $packed
        );

        $unpacked = (new Unpacker())->unpack($packed);

        $this->assertInstanceOf(Uuid::class, $unpacked);
        $this->assertEquals($expected, $unpacked);
        $this->assertSame($expected->value, $unpacked->value);
    }
}
