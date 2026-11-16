<?php

namespace Bolt\protocol\v3;

use Bolt\enum\Message;
use Bolt\error\BoltException;

trait BeginMessage
{
    /**
     * Send BEGIN message
     * The BEGIN message request the creation of a new Explicit Transaction.
     *
     * @link https://www.neo4j.com/docs/bolt/current/bolt/message/#messages-begin
     * @throws BoltException
     */
    public function begin(array $extra = []): static
    {
        $this->write($this->packer->pack(0x11, (object) $extra));
        $this->pipelinedMessages[] = Message::BEGIN;
        return $this;
    }
}
