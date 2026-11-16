<?php

namespace Bolt\protocol\v3;

use Bolt\enum\Message;
use Bolt\protocol\Response;
use Bolt\error\BoltException;

trait CommitMessage
{
    /**
     * Send COMMIT message
     * The COMMIT message request that the Explicit Transaction is done.
     *
     * @link https://www.neo4j.com/docs/bolt/current/bolt/message/#messages-commit
     * @throws BoltException
     */
    public function commit(): static
    {
        $this->write($this->packer->pack(0x12));
        $this->pipelinedMessages[] = Message::COMMIT;
        return $this;
    }

    /**
     * Read COMMIT response
     * @return iterable
     * @throws BoltException
     */
    protected function readCommit(): iterable
    {
        $this->openStreams = 0;
        $content = $this->read($signature);
        yield new Response(Message::COMMIT, $signature, $content);
    }
}
