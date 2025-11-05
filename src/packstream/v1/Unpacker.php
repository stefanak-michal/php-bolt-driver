<?php

namespace Bolt\packstream\v1;

use Bolt\error\UnpackException;
use Bolt\packstream\Bytes;
use Bolt\packstream\IUnpacker;
use Bolt\protocol\IStructure;

/**
 * Class Unpacker of PackStream version 1
 *
 * @author Michal Stefanak
 * @link https://github.com/neo4j-php/Bolt
 * @package Bolt\packstream\v1
 */
class Unpacker implements IUnpacker
{
    private string $message;
    private int $offset;
    private bool $littleEndian;
    private int $signature;

    /**
     * @inheritDoc
     */
    public function __construct(private readonly array $structuresLt = [])
    {
        $this->littleEndian = unpack('S', "\x01\x00")[1] === 1;
    }

    public function unpack(string $msg): mixed
    {
        if (empty($msg)) {
            return null;
        }

        $this->offset = 0;
        $this->message = $msg;

        return $this->u();
    }

    /**
     * @inheritDoc
     */
    public function getSignature(): int
    {
        return $this->signature;
    }

    /**
     * Get next bytes from message
     */
    private function next(int $length): string
    {
        $str = '';
        if (mb_strlen($this->message, '8bit') > $this->offset) {
            $str = mb_strcut($this->message, $this->offset, $length, '8bit');
            $this->offset += mb_strlen($str, '8bit');
        }
        return $str;
    }

    /**
     * @throws UnpackException
     */
    private function u(): mixed
    {
        $str = $this->next(1);
        if (mb_strlen($str, '8bit') !== 1) {
            return null;
        }
        $marker = ord($str);

        if ($marker == 0xC3) {
            return true;
        }
        if ($marker == 0xC2) {
            return false;
        }
        if ($marker == 0xC0) {
            return null;
        }

        $output = $this->unpackInteger($marker);
        if ($output !== null) {
            return $output;
        }
        $output = $this->unpackFloat($marker);
        if ($output !== null) {
            return $output;
        }
        $output = $this->unpackString($marker);
        if ($output !== null) {
            return $output;
        }
        $output = $this->unpackList($marker);
        if ($output !== null) {
            return $output;
        }
        $output = $this->unpackDictionary($marker);
        if ($output !== null) {
            return $output;
        }
        $output = $this->unpackStructure($marker);
        if ($output !== null) {
            return $output;
        }
        $output = $this->unpackByteArray($marker);
        if ($output !== null) {
            return $output;
        }

        return null;
    }

    /**
     * @param int $marker
     * @return array|IStructure|null
     * @throws UnpackException
     */
    private function unpackStructure(int $marker): array|IStructure|null
    {
        if ($marker >> 4 == 0b1011) { //TINY_STRUCT
            $size = 0b10110000 ^ $marker;
        } elseif ($marker == 0xDC) { //STRUCT_8
            $size = unpack('C', $this->next(1));
        } elseif ($marker == 0xDD) { //STRUCT_16
            $size = unpack('n', $this->next(2));
        } else {
            return null;
        }

        $signature = ord($this->next(1));

        if (array_key_exists($signature, $this->structuresLt)) {
            $values = [];
            for ($i = 0; $i < $size; $i++) {
                $values[] = $this->u();
            }

            $class = $this->structuresLt[$signature];
            try {
                $reflection = new \ReflectionClass($class);
                if ($reflection->getConstructor()->getNumberOfParameters() != count($values))
                    throw new UnpackException('Incorrect amount of structure fields for ' . $class);
            } catch (\ReflectionException $e) {
                throw new UnpackException($e->getMessage());
            }
            return new $class(...$values);
        } else {
            $this->signature = $signature;
            return $this->u() ?? [];
        }
    }

    /**
     * @param int $marker
     * @return array|null
     * @throws UnpackException
     */
    private function unpackDictionary(int $marker): ?array
    {
        if ($marker >> 4 == 0b1010) { //TINY_DICT
            $size = 0b10100000 ^ $marker;
        } elseif ($marker == 0xD8) { //DICT_8
            $size = (int)unpack('C', $this->next(1))[1];
        } elseif ($marker == 0xD9) { //DICT_16
            $size = (int)unpack('n', $this->next(2))[1];
        } elseif ($marker == 0xDA) { //DICT_32
            $size = (int)unpack('N', $this->next(4))[1];
        } else {
            return null;
        }

        $output = [];
        for ($i = 0; $i < $size; $i++) {
            $output[$this->u()] = $this->u();
        }
        return $output;
    }

    /**
     * @param int $marker
     * @return string|null
     */
    private function unpackString(int $marker): ?string
    {
        if ($marker >> 4 == 0b1000) { //TINY_STRING
            $length = 0b10000000 ^ $marker;
        } elseif ($marker == 0xD0) { //STRING_8
            $length = (int)unpack('C', $this->next(1))[1];
        } elseif ($marker == 0xD1) { //STRING_16
            $length = (int)unpack('n', $this->next(2))[1];
        } elseif ($marker == 0xD2) { //STRING_32
            $length = (int)unpack('N', $this->next(4))[1];
        } else {
            return null;
        }

        return $this->next($length);
    }

    /**
     * @param int $marker
     * @return int|null
     * @throws UnpackException
     */
    private function unpackInteger(int $marker): ?int
    {
        if ($marker >> 4 >= 0xF || $marker >> 4 <= 0x7) { //TINY_INT
            return (int)unpack('c', chr($marker))[1];
        } elseif ($marker == 0xC8) { //INT_8
            return (int)unpack('c', $this->next(1))[1];
        } elseif ($marker == 0xC9) { //INT_16
            $value = $this->next(2);
            return (int)unpack('s', $this->littleEndian ? strrev($value) : $value)[1];
        } elseif ($marker == 0xCA) { //INT_32
            $value = $this->next(4);
            return (int)unpack('l', $this->littleEndian ? strrev($value) : $value)[1];
        } elseif ($marker == 0xCB) { //INT_64
            $value = $this->next(8);
            if (PHP_INT_SIZE == 4) {
                throw new UnpackException('It is not possible to unpack received 64-bit integer because the environment is running 32-bit version of PHP.');
            }
            return (int)unpack('q', $this->littleEndian ? strrev($value) : $value)[1];
        } else {
            return null;
        }
    }

    /**
     * @param int $marker
     * @return float|null
     */
    private function unpackFloat(int $marker): ?float
    {
        if ($marker == 0xC1) {
            $value = $this->next(8);
            return (float)unpack('E', $value)[1];
        } else {
            return null;
        }
    }

    /**
     * @param int $marker
     * @return array|null
     * @throws UnpackException
     */
    private function unpackList(int $marker): ?array
    {
        if ($marker >> 4 == 0b1001) { //TINY_LIST
            $size = 0b10010000 ^ $marker;
        } elseif ($marker == 0xD4) { //LIST_8
            $size = (int)unpack('C', $this->next(1))[1];
        } elseif ($marker == 0xD5) { //LIST_16
            $size = (int)unpack('n', $this->next(2))[1];
        } elseif ($marker == 0xD6) { //LIST_32
            $size = (int)unpack('N', $this->next(4))[1];
        } else {
            return null;
        }

        $output = [];
        for ($i = 0; $i < $size; $i++) {
            $output[] = $this->u();
        }

        return $output;
    }

    /**
     * @param int $marker
     * @return Bytes|null
     */
    private function unpackByteArray(int $marker): ?Bytes
    {
        if ($marker == 0xCC) {
            $size = (int)unpack('C', $this->next(1))[1];
        } elseif ($marker == 0xCD) {
            $size = (int)unpack('n', $this->next(2))[1];
        } elseif ($marker == 0xCE) {
            $size = (int)unpack('N', $this->next(4))[1];
        } else {
            return null;
        }

        return new Bytes(mb_str_split($this->next($size), 1, '8bit'));
    }

}
