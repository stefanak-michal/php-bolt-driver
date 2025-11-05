<?php

namespace Bolt\protocol\v3;

use Bolt\enum\Message;
use Bolt\error\BoltException;

trait HelloMessage
{
    /**
     * Send HELLO message
     * The HELLO message request the connection to be authorized for use with the remote database.
     *
     * @link https://www.neo4j.com/docs/bolt/current/bolt/message/#messages-hello
     * @param array $extra
     * @throws BoltException
     */
    public function hello(array $extra): static
    {
        $this->write($this->packer->pack(0x01, $extra));
        $this->pipelinedMessages[] = Message::HELLO;
        return $this;
    }
}
