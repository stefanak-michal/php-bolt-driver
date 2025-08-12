<?php

namespace Bolt\protocol\v6\structures;

use Bolt\packstream\Bytes;
use Bolt\protocol\IStructure;

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
    ) {
    }

    public function __toString(): string
    {
        return json_encode([(string)$this->type_marker, (string)$this->data]);
    }

    private static array $formats = ['s', 'l', 'q'];

    /**
     * Encode array as vector structure
     * @param int[]|float[] $data
     * @return self
     * @throws \InvalidArgumentException
     */
    public static function encode(array $data): self
    {
        if (count($data) === 0) {
            throw new \InvalidArgumentException('Vector cannot be empty');
        }
        if (count($data) > 4096) {
            throw new \InvalidArgumentException('Vector cannot have more than 4096 elements');
        }

        $allIntegers = array_reduce($data, fn($carry, $item) => $carry && is_int($item), true);
        $allFloats = array_reduce($data, fn($carry, $item) => $carry && is_float($item), true);

        // Check if all values are integer or float
        if (!$allIntegers && !$allFloats) {
            throw new \InvalidArgumentException('All values in the vector must be integer xor float');
        }
        
        $minValue = min($data);
        $maxValue = max($data);
        $marker = 0;
        $packed = [];
        $packFormat = '';

        if ($allIntegers) {
            if ($minValue >= -128 && $maxValue <= 127) { // INT_8
                $marker = 0xC8;
                $packFormat = 'c';
            } elseif ($minValue >= -32768 && $maxValue <= 32767) { // INT_16
                $marker = 0xC9;
                $packFormat = 's';
            } elseif ($minValue >= -2147483648 && $maxValue <= 2147483647) { // INT_32
                $marker = 0xCA;
                $packFormat = 'l';
            } else { // INT_64
                $marker = 0xCB;
                $packFormat = 'q';
            }
        } elseif ($allFloats) {
            if ($minValue >= 1.4e-45 && $maxValue <= 3.4028235e+38) { // Single precision float (FLOAT_32)
                $marker = 0xC6;
                $packFormat = 'G';
            } else { // Double precision float (FLOAT_64)
                $marker = 0xC1;
                $packFormat = 'E';
            }
        }

        if ($marker === 0) {
            throw new \InvalidArgumentException('Unsupported data type for vector');
        }

        // Pack the data
        $littleEndian = unpack('S', "\x01\x00")[1] === 1;
        foreach ($data as $entry) {
            $value = pack($packFormat, $entry);
            $packed[] = in_array($packFormat, self::$formats) && $littleEndian ? strrev($value) : $value;
        }

        return new self(new Bytes([chr($marker)]), new Bytes($packed));
    }

    /**
     * Decode vector structure .. returns binary $this->data as array
     * @return int[]|float[]
     * @throws \InvalidArgumentException
     */
    public function decode(): array
    {
        switch (ord($this->type_marker[0])) {
            case 0xC8: // INT_8
                $size = 1;
                $unpackFormat = 'c';
                break;
            case 0xC9: // INT_16
                $size = 2;
                $unpackFormat = 's';
                break;
            case 0xCA: // INT_32
                $size = 4;
                $unpackFormat = 'l';
                break;
            case 0xCB: // INT_64
                $size = 8;
                $unpackFormat = 'q';
                break;
            case 0xC6: // FLOAT_32
                $size = 4;
                $unpackFormat = 'G';
                break;
            case 0xC1: // FLOAT_64
                $size = 8;
                $unpackFormat = 'E';
                break;
            default:
                throw new \InvalidArgumentException('Unknown vector type marker: ' . $this->type_marker[0]);
        }
        
        $output = [];
        $littleEndian = unpack('S', "\x01\x00")[1] === 1;
        foreach(mb_str_split((string)$this->data, $size, '8bit') as $value) {
            $output[] = unpack($unpackFormat, in_array($unpackFormat, self::$formats) && $littleEndian ? strrev($value) : $value)[1];
        }

        return $output;
    }
}
