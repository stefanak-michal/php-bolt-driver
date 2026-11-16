<?php

namespace Bolt\protocol\v1;

use Bolt\enum\Message;
use Bolt\error\BoltException;

trait RunMessage
{
    /**
     * Send RUN message
     * A RUN message submits a new query for execution, the result of which will be consumed by a subsequent message, such as PULL_ALL.
     *
     * @link https://www.neo4j.com/docs/bolt/current/bolt/message/#messages-run
     * @throws BoltException
     */
    public function run(string $query, array $parameters = []): static
    {
        $this->write($this->packer->pack(0x10, $query, (object) $parameters));
        $this->pipelinedMessages[] = Message::RUN;
        return $this;
    }
}
