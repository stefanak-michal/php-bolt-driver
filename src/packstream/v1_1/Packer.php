<?php

namespace Bolt\packstream\v1_1;

/**
 * Class Packer of PackStream version 1.1
 *
 * @author Michal Stefanak
 * @link https://github.com/stefanak-michal/php-bolt-driver
 * @package Bolt\packstream\v1_1
 */
class Packer extends \Bolt\packstream\v1\Packer
{
    protected function packValue(mixed $param): iterable
    {
        if ($param instanceof Uuid) {
            return yield from $this->packUuid($param);
        }

        yield from parent::packValue($param);
    }

    private function packUuid(Uuid $uuid): iterable
    {
        yield chr(0xE0) . hex2bin(str_replace('-', '', $uuid->value));
    }
}
