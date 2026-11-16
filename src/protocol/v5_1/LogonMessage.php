<?php

namespace Bolt\protocol\v5_1;

use Bolt\enum\Message;
use Bolt\error\BoltException;

trait LogonMessage
{
    /**
     * Send LOGON message
     * A LOGON message carries an authentication request.
     *
     * @link https://www.neo4j.com/docs/bolt/current/bolt/message/#messages-logon
     * @throws BoltException
     */
    public function logon(array $auth): static
    {
        $this->write($this->packer->pack(0x6A, (object) $auth));
        $this->pipelinedMessages[] = Message::LOGON;
        return $this;
    }
}
