<?php

namespace Bolt\tests\structures\v1;

use Bolt\Bolt;
use Bolt\protocol\{
    AProtocol,
    V3,
    V4_2,
    V4_3,
    V4_4
};
use Bolt\protocol\v1\structures\{
    Date,
    DateTime,
    DateTimeZoneId,
    Duration,
    LocalDateTime,
    LocalTime,
    Node,
    Path,
    Point2D,
    Point3D,
    Relationship,
    Time,
    UnboundRelationship
};
use Bolt\enum\Signature;
use Bolt\packstream\Bytes;

/**
 * Class StructuresTest
 *
 * @author Michal Stefanak
 * @link https://github.com/neo4j-php/Bolt
 * @package Bolt\tests
 */
class StructuresTest extends \Bolt\tests\structures\StructureLayer
{
    public function testInit(): AProtocol|V4_4|V4_3|V4_2|V3
    {
        $conn = new \Bolt\connection\StreamSocket($GLOBALS['NEO_HOST'] ?? '127.0.0.1', $GLOBALS['NEO_PORT'] ?? 7687);
        $this->assertInstanceOf(\Bolt\connection\StreamSocket::class, $conn);

        $bolt = new Bolt($conn);
        $this->assertInstanceOf(Bolt::class, $bolt);

        $bolt->setProtocolVersions(4.4, 4.3, 4.2, 3);
        /** @var AProtocol|V4_4|V4_3|V4_2|V3 $protocol */
        $protocol = $bolt->build();
        $this->assertInstanceOf(AProtocol::class, $protocol);

        $this->assertEquals(Signature::SUCCESS, $protocol->hello([
            'user_agent' => 'bolt-php',
            'scheme' => 'basic',
            'principal' => $GLOBALS['NEO_USER'],
            'credentials' => $GLOBALS['NEO_PASS'],
        ])->getResponse()->signature);

        return $protocol;
    }

    /**
     * @depends      testInit
     * @dataProvider providerTimestamp
     */
    public function testDate(int $timestamp, AProtocol|V4_4|V4_3|V4_2|V3 $protocol): void
    {
        $date = gmdate('Y-m-d', $timestamp);

        //unpack
        $res = iterator_to_array(
            $protocol
                ->run('RETURN date($date)', [
                    'date' => $date
                ], ['mode' => 'r'])
                ->pull()
                ->getResponses(),
            false
        );
        $dateStructure = $res[1]->content[0];

        $this->assertInstanceOf(Date::class, $dateStructure);
        $this->assertEquals($date, (string)$dateStructure, 'unpack ' . $date . ' != ' . $dateStructure);

        //pack
        $res = iterator_to_array(
            $protocol
                ->run('RETURN toString($date)', [
                    'date' => $dateStructure
                ], ['mode' => 'r'])
                ->pull()
                ->getResponses(),
            false
        );
        $this->assertEquals($date, $res[1]->content[0], 'pack ' . $date . ' != ' . $res[1]->content[0]);
    }

    private string $expectedDateTimeClass = DateTime::class;
    use DateTimeTrait;

    private string $expectedDateTimeZoneIdClass = DateTimeZoneId::class;
    use DateTimeZoneIdTrait;

    /**
     * @depends      testInit
     * @dataProvider durationProvider
     */
    public function testDuration(string $duration, AProtocol|V4_4|V4_3|V4_2|V3 $protocol): void
    {
        //unpack
        $res = iterator_to_array(
            $protocol
                ->run('RETURN duration($d)', ['d' => $duration], ['mode' => 'r'])
                ->pull()
                ->getResponses(),
            false
        );
        $durationStructure = $res[1]->content[0];

        $this->assertInstanceOf(Duration::class, $durationStructure);
        $this->assertEquals($duration, (string)$durationStructure, 'unpack ' . $duration . ' != ' . $durationStructure);

        //pack
        $res = iterator_to_array(
            $protocol
                ->run('RETURN toString($d)', [
                    'd' => $durationStructure
                ], ['mode' => 'r'])
                ->pull()
                ->getResponses(),
            false
        );

        $this->assertEquals($duration, $res[1]->content[0], 'pack ' . $duration . ' != ' . $res[1]->content[0]);
    }

    public function durationProvider(): \Generator
    {
        foreach ([
                     'P1Y',
                     'P1M',
                     'P1D',
                     'PT1H',
                     'PT1M',
                     'PT1S',
                     'P1Y2M14DT16H12M35.765S'
                 ] as $duration)
            yield $duration => [$duration];
    }

    /**
     * @depends      testInit
     * @dataProvider providerTimestamp
     */
    public function testLocalDateTime(int $timestamp, AProtocol|V4_4|V4_3|V4_2|V3 $protocol): void
    {
        $timestamp .= '.' . rand(0, 9e5);
        $datetime = \DateTime::createFromFormat('U.u', $timestamp)
            ->format('Y-m-d\TH:i:s.u');

        //unpack
        $res = iterator_to_array(
            $protocol
                ->run('RETURN localdatetime($dt)', [
                    'dt' => $datetime
                ], ['mode' => 'r'])
                ->pull()
                ->getResponses(),
            false
        );
        $localDateTimeStructure = $res[1]->content[0];

        $this->assertInstanceOf(LocalDateTime::class, $localDateTimeStructure);
        $this->assertEquals($datetime, (string)$localDateTimeStructure, 'unpack ' . $datetime . ' != ' . $localDateTimeStructure);

        //pack
        $res = iterator_to_array(
            $protocol
                ->run('RETURN toString($dt)', [
                    'dt' => $localDateTimeStructure
                ], ['mode' => 'r'])
                ->pull()
                ->getResponses(),
            false
        );
        $datetime = rtrim($datetime, '.0');
        $this->assertEquals($datetime, $res[1]->content[0], 'pack ' . $datetime . ' != ' . $res[1]->content[0]);
    }

    /**
     * @depends      testInit
     * @dataProvider providerTimestamp
     */
    public function testLocalTime(int $timestamp, AProtocol|V4_4|V4_3|V4_2|V3 $protocol): void
    {
        $timestamp .= '.' . rand(0, 9e5);
        $time = \DateTime::createFromFormat('U.u', $timestamp)
            ->format('H:i:s.u');

        //unpack
        $res = iterator_to_array(
            $protocol
                ->run('RETURN localtime($t)', [
                    't' => $time
                ], ['mode' => 'r'])
                ->pull()
                ->getResponses(),
            false
        );
        $localTimeStructure = $res[1]->content[0];

        $this->assertInstanceOf(LocalTime::class, $localTimeStructure);
        $this->assertEquals($time, (string)$localTimeStructure, 'unpack ' . $time . ' != ' . $localTimeStructure);

        //pack
        $res = iterator_to_array(
            $protocol
                ->run('RETURN toString($t)', [
                    't' => $localTimeStructure
                ], ['mode' => 'r'])
                ->pull()
                ->getResponses(),
            false
        );
        $time = rtrim($time, '.0');
        $this->assertEquals($time, $res[1]->content[0], 'pack ' . $time . ' != ' . $res[1]->content[0]);
    }

    /**
     * @depends testInit
     */
    public function testNode(AProtocol|V4_4|V4_3|V4_2|V3 $protocol): void
    {
        $protocol->begin()->getResponse();

        //unpack
        $res = iterator_to_array(
            $protocol
                ->run('CREATE (a:Test { param1: 123 }) RETURN a, ID(a)')
                ->pull()
                ->getResponses(),
            false
        );
        $this->assertInstanceOf(Node::class, $res[1]->content[0]);

        $this->assertEquals($res[1]->content[1], $res[1]->content[0]->id);
        $this->assertEquals(['Test'], $res[1]->content[0]->labels);
        $this->assertEquals(['param1' => 123], $res[1]->content[0]->properties);

        //pack not supported

        $protocol->rollback()->getResponse();
    }

    /**
     * @depends testInit
     */
    public function testPath(AProtocol|V4_4|V4_3|V4_2|V3 $protocol): void
    {
        $protocol->begin()->getResponse();

        //unpack
        $res = iterator_to_array(
            $protocol
                ->run('CREATE p=(:Test)-[r:HAS { param1: 123 }]->(:Test) RETURN p, ID(r)')
                ->pull()
                ->getResponses(),
            false
        );
        $this->assertInstanceOf(Path::class, $res[1]->content[0]);

        foreach ($res[1]->content[0]->rels as $rel) {
            $this->assertInstanceOf(UnboundRelationship::class, $rel);

            $this->assertEquals($res[1]->content[1], $rel->id);
            $this->assertEquals('HAS', $rel->type);
            $this->assertEquals(['param1' => 123], $rel->properties);
        }

        //pack not supported

        $protocol->rollback()->getResponse();
    }

    /**
     * @depends testInit
     */
    public function testPoint2D(AProtocol|V4_4|V4_3|V4_2|V3 $protocol): void
    {
        //unpack
        $res = iterator_to_array(
            $protocol
                ->run('RETURN point({ latitude: 13.43, longitude: 56.21 })', [], ['mode' => 'r'])
                ->pull()
                ->getResponses(),
            false
        );
        $this->assertInstanceOf(Point2D::class, $res[1]->content[0]);

        //pack
        $res = iterator_to_array(
            $protocol
                ->run('RETURN toString($p)', [
                    'p' => $res[1]->content[0]
                ], ['mode' => 'r'])
                ->pull()
                ->getResponses(),
            false
        );
        $this->assertStringStartsWith('point(', $res[1]->content[0]);
    }

    /**
     * @depends testInit
     */
    public function testPoint3D(AProtocol|V4_4|V4_3|V4_2|V3 $protocol): void
    {
        //unpack
        $res = iterator_to_array(
            $protocol
                ->run('RETURN point({ x: 0, y: 4, z: 1 })', [], ['mode' => 'r'])
                ->pull()
                ->getResponses(),
            false
        );
        $this->assertInstanceOf(Point3D::class, $res[1]->content[0]);

        //pack
        $res = iterator_to_array(
            $protocol
                ->run('RETURN toString($p)', [
                    'p' => $res[1]->content[0]
                ], ['mode' => 'r'])
                ->pull()
                ->getResponses(),
            false
        );
        $this->assertStringStartsWith('point(', $res[1]->content[0]);
    }

    /**
     * @depends testInit
     */
    public function testRelationship(AProtocol|V4_4|V4_3|V4_2|V3 $protocol): void
    {
        $protocol->begin()->getResponse();

        //unpack
        $res = iterator_to_array(
            $protocol
                ->run('CREATE (a:Test)-[rel:HAS { param1: 123 }]->(b:Test) RETURN rel, ID(rel), ID(a), ID(b)')
                ->pull()
                ->getResponses(),
            false
        );
        $this->assertInstanceOf(Relationship::class, $res[1]->content[0]);

        $this->assertEquals($res[1]->content[1], $res[1]->content[0]->id);
        $this->assertEquals('HAS', $res[1]->content[0]->type);
        $this->assertEquals(['param1' => 123], $res[1]->content[0]->properties);
        $this->assertEquals($res[1]->content[2], $res[1]->content[0]->startNodeId);
        $this->assertEquals($res[1]->content[3], $res[1]->content[0]->endNodeId);

        //pack not supported

        $protocol->rollback()->getResponse();
    }

    /**
     * @depends      testInit
     * @dataProvider providerTimestampTimezone
     */
    public function testTime(int $timestamp, string $timezone, AProtocol|V4_4|V4_3|V4_2|V3 $protocol): void
    {
        $timestamp .= '.' . rand(0, 9e5);
        $time = \DateTime::createFromFormat('U.u', $timestamp, new \DateTimeZone($timezone))
            ->format('H:i:s.uP');

        //unpack
        $res = iterator_to_array(
            $protocol
                ->run('RETURN time($t)', [
                    't' => $time
                ], ['mode' => 'r'])
                ->pull()
                ->getResponses(),
            false
        );
        $timeStructure = $res[1]->content[0];

        $this->assertInstanceOf(Time::class, $timeStructure);
        $this->assertEquals($time, (string)$timeStructure, 'unpack ' . $time . ' != ' . $timeStructure);

        //pack
        $res = iterator_to_array(
            $protocol
                ->run('RETURN toString($t)', [
                    't' => $timeStructure
                ], ['mode' => 'r'])
                ->pull()
                ->getResponses(),
            false
        );

        // neo4j returns fraction of seconds not padded with zeros ... zero timezone offset returns as Z
        $time = preg_replace(["/\.?0+(.\d{2}:\d{2})$/", "/\+00:00$/"], ['$1', 'Z'], $time);
        $this->assertEquals($time, $res[1]->content[0], 'pack ' . $time . ' != ' . $res[1]->content[0]);
    }

    /**
     * @depends testInit
     */
    public function testBytes(AProtocol|V4_4|V4_3|V4_2|V3 $protocol): void
    {
        $bytes = new Bytes([0x00, 0x01, 0x02, 0x03, 0x04, 0x05, 0x06, 0x07, 0x08, 0x09]);
        $res = iterator_to_array(
            $protocol
                ->begin()
                ->run('CREATE (:Test { k: 123, b: $b })', [
                    'b' => $bytes
                ], ['mode' => 'w'])
                ->run('MATCH (n:Test { k: 123 }) RETURN n.b')
                ->pull()
                ->rollback()
                ->getResponses(),
            false
        );
        $this->assertInstanceOf(Bytes::class, $res[3]->content[0]);
        $this->assertEquals(
            (string)$bytes,
            (string)($res[3]->content[0])
        );
    }
}
