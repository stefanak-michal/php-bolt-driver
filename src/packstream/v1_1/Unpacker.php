<?php

namespace Bolt\packstream\v1_1;

/**
 * Class Unpacker of PackStream version 1.1
 *
 * @author Michal Stefanak
 * @link https://github.com/stefanak-michal/php-bolt-driver
 * @package Bolt\packstream\v1_1
 */
class Unpacker extends \Bolt\packstream\v1\Unpacker
{
    protected function unpackValue(?int $marker): mixed
    {
        $output = $this->unpackUuid($marker);
        if ($output !== null) {
            return $output;
        }
        return parent::unpackValue($marker);
    }

    private function unpackUuid(int $marker): ?Uuid
    {
        if ($marker == 0xE0) {
            $hex = bin2hex($this->next(16));
            return new Uuid(
                substr($hex, 0, 8)
                . '-' . substr($hex, 8, 4)
                . '-' . substr($hex, 12, 4)
                . '-' . substr($hex, 16, 4)
                . '-' . substr($hex, 20, 12)
            );
        }

        return null;
    }
}
