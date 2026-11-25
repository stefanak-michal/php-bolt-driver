<?php

namespace Bolt\protocol;

use Bolt\enum\{Message, Signature};

/**
 * @author Michal Stefanak
 * @link https://github.com/stefanak-michal/php-bolt-driver
 * @package Bolt\protocol
 */
readonly class Response
{
    /**
     * @param Message $message Requested bolt message name
     * @param Signature $signature Signature tells you result of your request
     * @param array $content Response content
     */
    public function __construct(
        public Message $message,
        public Signature $signature,
        public array $content = [],
    ) {}
}
