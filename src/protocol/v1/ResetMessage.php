<?php

namespace Bolt\protocol\v1;

use Bolt\enum\Message;
use Bolt\error\BoltException;

trait ResetMessage
{
    /**
     * Send RESET message
     * The RESET message requests that the connection should be set back to its initial READY state, as if an INIT
     * had just successfully completed.
     *
     * @link https://www.neo4j.com/docs/bolt/current/bolt/message/#messages-reset
     * @throws BoltException
     */
    public function reset(): static
    {
        $this->write($this->packer->pack(0x0F));
        $this->pipelinedMessages[] = Message::RESET;
        return $this;
    }
}
