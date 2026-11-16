<?php

namespace Bolt\protocol\v3;

use Bolt\enum\Message;
use Bolt\protocol\Response;
use Bolt\error\BoltException;

trait RollbackMessage
{
    /**
     * Send ROLLBACK message
     * The ROLLBACK message requests that the Explicit Transaction rolls back.
     *
     * @link https://www.neo4j.com/docs/bolt/current/bolt/message/#messages-rollback
     * @throws BoltException
     */
    public function rollback(): static
    {
        $this->write($this->packer->pack(0x13));
        $this->pipelinedMessages[] = Message::ROLLBACK;
        return $this;
    }

    /**
     * Read ROLLBACK response
     * @return iterable
     * @throws BoltException
     */
    protected function readRollback(): iterable
    {
        $this->openStreams = 0;
        $content = $this->read($signature);
        yield new Response(Message::ROLLBACK, $signature, $content);
    }
}
