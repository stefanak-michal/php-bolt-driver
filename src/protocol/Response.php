<?php

namespace Bolt\protocol;

use Bolt\enum\{Message, Signature};

/**
 * Class Response
 * @author Michal Stefanak
 * @link https://github.com/stefanak-michal/php-bolt-driver
 * @package Bolt\protocol
 */
class Response
{
    /**
     * @param Message $message Requested bolt message name
     * @param Signature $signature Signature tells you result of your request
     * @param array $content Response content
     */
    public function __construct(
        public readonly Message   $message,
        public readonly Signature $signature,
        public readonly array     $content = []
    )
    {
    }
}
