<?php

namespace Bolt\packstream;

use Bolt\error\UnpackException;

/**
 * Interface IUnpacker
 *
 * @author Michal Stefanak
 * @link https://github.com/stefanak-michal/php-bolt-driver
 * @package Bolt\packstream
 */
interface IUnpacker
{
    /**
     * @param array $structuresLt [signature => classFQN]
     */
    public function __construct(array $structuresLt = []);

    /**
     * Unpack message
     * @throws UnpackException
     */
    public function unpack(string $msg): mixed;

    /**
     * Get unpacked message status signature
     */
    public function getSignature(): int;
}
