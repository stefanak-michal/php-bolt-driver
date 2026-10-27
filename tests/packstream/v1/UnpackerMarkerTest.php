<?php

namespace Bolt\tests\packstream\v1;

use Bolt\error\UnpackException;
use Bolt\packstream\v1\Unpacker;
use PHPUnit\Framework\TestCase;

/**
 * Offline unit tests for PackStream 1.0 unknown / reserved markers.
 *
 * UUID marker 0xE0 is defined only in PackStream 1.1 (Bolt >= 6.1).
 * PackStream requires reserved markers in an incoming stream to be treated as an error.
 *
 * @link https://neo4j.com/docs/bolt/current/packstream/
 */
class UnpackerMarkerTest extends TestCase
{
    public function testUuidMarkerThrowsUnpackException(): void
    {
        $payload = chr(0xE0) . hex2bin('550e8400e29b41d4a716446655440000');

        $this->expectException(UnpackException::class);
        $this->expectExceptionMessage('Unknown PackStream marker 0xe0');

        (new Unpacker())->unpack($payload);
    }

    public function testUuidMarkerDoesNotReturnNull(): void
    {
        $payload = chr(0xE0) . hex2bin('550e8400e29b41d4a716446655440000');

        try {
            (new Unpacker())->unpack($payload);
            $this->fail('Expected UnpackException for marker 0xE0');
        } catch (UnpackException $e) {
            $this->assertStringContainsString('0xe0', strtolower($e->getMessage()));
        }
    }
}
