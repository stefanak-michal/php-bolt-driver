<?php

namespace Bolt\protocol\v1;

use Bolt\enum\Message;
use Bolt\error\BoltException;

trait AckFailureMessage
{
    /**
     * When requests fail on the server, the server will send the client a FAILURE message.
     * The client must acknowledge the FAILURE message by sending an ACK_FAILURE message to the server.
     * Until the server receives the ACK_FAILURE message, it will send an IGNORED message in response
     * to any other message from the client.
     *
     * @link https://www.neo4j.com/docs/bolt/current/bolt/message/#messages-ack-failure
     * @throws BoltException
     */
    public function ackFailure(): static
    {
        $this->write($this->packer->pack(0x0E));
        $this->pipelinedMessages[] = Message::ACK_FAILURE;
        return $this;
    }
}
