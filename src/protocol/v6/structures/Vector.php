<?php

namespace Bolt\protocol\v6\structures;

use Bolt\packstream\Bytes;
use Bolt\protocol\IStructure;
use Bolt\protocol\v6\structures\TypeMarker;

/**
 * Class Vector
 * Immutable
 *
 * @author Michal Stefanak
 * @link https://github.com/neo4j-php/Bolt
 * @link https://www.neo4j.com/docs/bolt/current/bolt/structure-semantics/#structure-vector
 * @package Bolt\protocol\v6\structures
 */
class Vector implements IStructure
{
    public function __construct(
        public readonly Bytes $type_marker,
        public readonly Bytes $data
    ) {}

    public function __toString(): string
    {
        return json_encode([(string)$this->type_marker, (string)$this->data]);
    }

    private static array $endiannessFormats = ['s', 'l', 'q'];

    /**
     * Encode array as vector structure
     * This is a helper method to create Vector structure from array of numbers
     * @param int[]|float[] $data
     * @param TypeMarker|null $type Optional type to force specific data type .. null = auto decide
     * @return self
     * @throws \InvalidArgumentException
     */
    public static function encode(array $data, ?TypeMarker $type = null): self
    {
        $anyFloat = false;
        foreach ($data as $entry) {
            if (!is_int($entry) && !is_float($entry)) {
                throw new \InvalidArgumentException('Vector can only contain numeric values');
            }
            if (!$anyFloat && is_float($entry)) {
                $anyFloat = true;
            }
        }

        if ($type === null) {
            $type = self::detectTypeMarker($anyFloat, count($data) ? min($data) : 0, count($data) ? max($data) : 0);
        }

        $packFormat = '';
        switch ($type) {
            case TypeMarker::FLOAT_32:
                $packFormat = 'G';
                break;
            case TypeMarker::FLOAT_64:
                $packFormat = 'E';
                break;
            case TypeMarker::INT_8:
                $packFormat = 'c';
                break;
            case TypeMarker::INT_16:
                $packFormat = 's';
                break;
            case TypeMarker::INT_32:
                $packFormat = 'l';
                break;
            case TypeMarker::INT_64:
                $packFormat = 'q';
                break;
        }

        // Pack the data
        $packed = [];
        $littleEndian = unpack('S', "\x01\x00")[1] === 1;
        foreach ($data as $entry) {
            $value = pack($packFormat, $anyFloat ? (float)$entry : (int)$entry);
            $packed[] = in_array($packFormat, self::$endiannessFormats) && $littleEndian ? strrev($value) : $value;
        }

        return new self(new Bytes([chr($type->value)]), new Bytes($packed));
    }

    private static function detectTypeMarker(bool $anyFloat, int|float $minValue, int|float $maxValue): TypeMarker
    {
        if ($anyFloat) {
            if ($minValue >= -3.4028235e+38 && $maxValue <= 3.4028235e+38) { // Single precision float (FLOAT_32)
                return TypeMarker::FLOAT_32;
            } else { // Double precision float (FLOAT_64)
                return TypeMarker::FLOAT_64;
            }
        } else {
            if ($minValue >= -128 && $maxValue <= 127) { // INT_8
                return TypeMarker::INT_8;
            } elseif ($minValue >= -32768 && $maxValue <= 32767) { // INT_16
                return TypeMarker::INT_16;
            } elseif ($minValue >= -2147483648 && $maxValue <= 2147483647) { // INT_32
                return TypeMarker::INT_32;
            } else { // INT_64
                return TypeMarker::INT_64;
            }
        }
    }

    /**
     * Decode vector structure .. returns binary $this->data as array of numbers
     * @return int[]|float[]
     * @throws \InvalidArgumentException
     */
    public function decode(): array
    {
        switch (ord($this->type_marker[0])) {
            case TypeMarker::INT_8->value: // INT_8
                $size = 1;
                $unpackFormat = 'c';
                break;
            case TypeMarker::INT_16->value: // INT_16
                $size = 2;
                $unpackFormat = 's';
                break;
            case TypeMarker::INT_32->value: // INT_32
                $size = 4;
                $unpackFormat = 'l';
                break;
            case TypeMarker::INT_64->value: // INT_64
                $size = 8;
                $unpackFormat = 'q';
                break;
            case TypeMarker::FLOAT_32->value: // FLOAT_32
                $size = 4;
                $unpackFormat = 'G';
                break;
            case TypeMarker::FLOAT_64->value: // FLOAT_64
                $size = 8;
                $unpackFormat = 'E';
                break;
            default:
                throw new \InvalidArgumentException('Unknown vector type marker: ' . $this->type_marker[0]);
        }

        $output = [];
        $littleEndian = unpack('S', "\x01\x00")[1] === 1;
        foreach (mb_str_split((string)$this->data, $size, '8bit') as $value) {
            $output[] = unpack($unpackFormat, in_array($unpackFormat, self::$endiannessFormats) && $littleEndian ? strrev($value) : $value)[1];
        }

        return $output;
    }
}
