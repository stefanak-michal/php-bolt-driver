<?php

namespace Bolt\enum;

/**
 * Enum Signature
 * @author Michal Stefanak
 * @link https://github.com/stefanak-michal/php-bolt-driver
 * @package Bolt\enum
 */
enum Signature: int
{
    case NONE = 0;
    /**
     * The SUCCESS message indicates that the corresponding request has succeeded as intended. It may contain metadata relating to the outcome. Metadata keys are described in the section of this document relating to the message that began the exchange.
     * @internal 112
     */
    case SUCCESS = 0x70;
    /**
     * A FAILURE message response indicates that the client is not permitted to exchange further messages. Servers may choose to include metadata describing the nature of the failure.
     * @internal 127
     */
    case FAILURE = 0x7F;
    /**
     * The IGNORED message indicates that the corresponding request has not been carried out.
     * @internal 126
     */
    case IGNORED = 0x7E;
    /**
     * A RECORD message carries a sequence of values corresponding to a single entry in a result.
     * These messages are currently only ever received in response to a PULL (PULL_ALL in v1, v2, and v3) message and will always be followed by a summary message.
     * @internal 113
     */
    case RECORD = 0x71;
}
